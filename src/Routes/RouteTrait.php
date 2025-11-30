<?php

namespace WPSPCORE\Routes;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

trait RouteTrait {

	public function isPassedMiddleware($middlewares = [], $request = null, $args = []): bool {
		// Không có middleware → pass
		if (empty($middlewares)) {
			return true;
		}

		// Mỗi phần tử trong $middlewares là 1 "middleware block"
		// Route PASS khi TẤT CẢ block PASS
		foreach ($middlewares as $blockMiddleware) {

			// Đưa thêm block đang xử lý vào args để truyền vào middleware.
			// Trong handle của middleware, có thể dùng $args['block_middleware'] để lấy block đang xử lý.
			$args['current_block_middleware'] = $blockMiddleware;

			// -----------------------------
			// 1. Đọc relation của block
			// -----------------------------
			$relation = 'AND';
			if (isset($blockMiddleware['relation'])) {
				$relation = strtoupper($blockMiddleware['relation']);
				unset($blockMiddleware['relation']);
			}

			// -----------------------------
			// 2. Chuẩn hoá middleware trong block
			// -----------------------------
			$normalized = [];
			foreach ($blockMiddleware as $mw) {
				if ($mw instanceof \Closure) {
					$normalized[] = [
						'type'    => 'closure',
						'closure' => $mw,
						'args'    => $args,
					];
					continue;
				}

				// [Class, method]
				if (is_array($mw) && isset($mw[0]) && is_string($mw[0])) {
					$normalized[] = [
						'type'   => 'class',
						'class'  => $mw[0],
						'method' => $mw[1] ?? 'handle',
						'args'   => $args,
					];
					continue;
				}

				// Class string
				if (is_string($mw)) {
					$normalized[] = [
						'type'   => 'class',
						'class'  => $mw,
						'method' => 'handle',
						'args'   => $args,
					];
					continue;
				}
			}

			// -----------------------------
			// 3. Hàm chạy từng middleware
			// -----------------------------
			$app     = $this->funcs->getApplication();
			$request = $app->make('request');

			$runOne = function($mw) use ($app, $request) {

				$next = function() {
					return new Response('', 200);
				};

				// Chỗ này không cần try-catch, vì middleware sẽ có thể throw Exception.
//				try {
					if ($mw['type'] === 'closure') {
						$res = call_user_func($mw['closure'], $request, $next);
					}
					else {
						$class  = $mw['class'];
						$method = $mw['method'];

						if (!class_exists($class)) {
							return false;
						}

						$instance = $app->make($class);

						// Ép method là handle nếu không có method chỉ định.
						// Nếu không có method handle thì sẽ xảy ra lỗi, có thể bắt lỗi trong Exception Handler.
						if (!method_exists($instance, $method)) {
//							if (method_exists($instance, 'handle')) {
								$method = 'handle';
//							}
//							else {
//								return false;
//							}
						}

						$res = $instance->$method($request, $next, $mw['args'] ?? []);
					}
//				}
//				catch (\Throwable $e) {
//					return false;
//				}

				if ($res instanceof Response) {
					return $res->getStatusCode() < 400;
				}

				if (is_bool($res)) return $res;

				return true;
			};

			// -----------------------------
			// 4. Evaluate block theo relation
			// -----------------------------

			if ($relation === 'OR') {
				$pass = false;
				foreach ($normalized as $mw) {
					if ($runOne($mw)) {
						$pass = true;
						break;
					}
				}
				if (!$pass) return false;
			}

			if ($relation === 'AND') {
				foreach ($normalized as $mw) {
					if (!$runOne($mw)) {
						return false;
					}
				}
			}
		}

		// TẤT CẢ block đều PASS
		return true;
	}

	public function prepareRouteCallback($callback, $constructParams = []) {

		// If callback is a closure.
		if ($callback instanceof \Closure) {
			return $callback;
		}

		// If callback is an array with class and method.
		if (is_array($callback)) {
			$class = new $callback[0](...$constructParams ?? []);
			return [$class, $callback[1] ?? null];
		}

		// If callback is a string.
		return function() use ($callback) {
			return $callback;
		};

	}

	public function getCallParams($path, $fullPath, $requestPath, $callbackOrClass, $method = null, $args = []): array {
		// NEW: detect closure
		if ($callbackOrClass instanceof \Closure) {
			$reflection = new \ReflectionFunction($callbackOrClass);
			$class      = null;
			$method     = null;
		}
		else {
			$class      = $callbackOrClass;
			$reflection = new \ReflectionMethod($class, $method);
		}

		// Match pattern: KHÔNG escape path vì path đã là regex pattern (có thể chứa (?P<name>...))
		// Nếu $path có ^ hoặc $ thì vẫn dùng như vậy; nếu không có, ta match toàn chuỗi.
		$regexPath = $this->funcs->_regexPath($path);
		$pattern   = '#' . $regexPath . '#iu';

		$passed = false;

		// Nếu nơi gọi hàm này là route "Ajaxs" với method POST, check action và match path.
		if (preg_match('/Ajaxs$/', static::class)) {
			$httpMethod = $this->request->getMethod();
			if ($httpMethod === 'POST') {
				$params = $this->request->all();
				$passed = isset($params['action']) && $params['action'] === $path;
			}
		}

		// Kiểm tra path có khớp với request path hiện tại không?
		if (preg_match($pattern, $requestPath, $matches)) {
			$passed = true;
		}

		if (!$passed) {
			// Build all params as null for primitive args
//			$reflection = new \ReflectionMethod($class, $method);
			$callParams = [];

			foreach ($reflection->getParameters() as $param) {
				$type = $param->getType();

				// Nếu type là class → container sẽ inject sau
				if ($type && !$type->isBuiltin()) {
					continue;
				}

				// Primitive → NULL
				$callParams[$param->getName()] = null;
			}

			// Thêm các giá trị hệ thống
			$callParams['path']        = $path ?? null;
			$callParams['fullPath']    = $fullPath ?? null;
			$callParams['requestPath'] = $requestPath ?? null;

			foreach ($args as $argKey => $argValue) {
				$callParams[$argKey] = $argValue;
			}

			return $callParams;
		}

		// Lấy container / request
		$app = $this->funcs->getApplication();
		if (!$app) {
			throw new \RuntimeException('Container instance not found when building call params.');
		}
		$baseRequest = $app->bound('request') ? $app->make('request') : ($this->request ?? Request::capture());

		// Named groups: keys là tên (PHP returns associative entries for named groups)
		$named = array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);

		// Positional captures (1..n)
		$positional = [];
		foreach ($matches as $k => $v) {
			if (is_int($k) && $k > 0) $positional[] = $v;
		}
		$posIndex = 0;

		// Request sources
		$query = $baseRequest->query->all();      // GET params
		$post  = $baseRequest->request->all();    // POST params
		$attr  = $baseRequest->attributes->all(); // attributes

		// Reflection method để đọc danh sách tham số của callback
//		$reflection = new \ReflectionMethod($class, $method);
		$callParams = [];

		foreach ($reflection->getParameters() as $param) {
			$name = $param->getName();
			$type = $param->getType();

			// Nếu param có type-hint là class (non-builtin) -> để container xử lý, KHÔNG gán value vào routeParams
			// (Container::call sẽ tự inject class instances)
			if ($type && !$type->isBuiltin()) {
				// Không set $callParams[$name] — container sẽ resolve type-hint
				continue;
			}

			$value = null;

			// 1) Nếu có named capture trùng tên param -> ưu tiên
			if (array_key_exists($name, $named)) {
				$value = $named[$name];
			}
			// 2) attributes (request attributes)
			elseif (array_key_exists($name, $attr)) {
				$value = $attr[$name];
			}
			// 3) POST (body)
			elseif (array_key_exists($name, $post)) {
				$value = $post[$name];
			}
			// 4) Query string
			elseif (array_key_exists($name, $query)) {
				$value = $query[$name];
			}
			// 5) Positional capture fallback
			elseif (isset($positional[$posIndex])) {
				$value = $positional[$posIndex++];
			}
			// 6) Default value from signature
			elseif ($param->isDefaultValueAvailable()) {
				$value = $param->getDefaultValue();
			}
			// 7) else null

			// Nếu là string, decode URL-encoded values (an toàn)
			if (is_string($value)) {
				$value = urldecode($value);
			}

			$callParams[$name] = $value;
		}

		$callParams['path']        = $path;
		$callParams['fullPath']    = $fullPath;
		$callParams['requestPath'] = $requestPath;

		foreach ($args as $argKey => $argValue) {
			$callParams[$argKey] = $argValue;
		}

		// Ngoài các params lấy từ signature (primitive params),
		// ta cũng muốn expose ALL named captures (dù method không khai báo param cụ thể)
		// — giúp bạn có thể lấy $routeParams['endpoint'] trong middleware hoặc log.
		foreach ($named as $k => $v) {
			if (!array_key_exists($k, $callParams)) {
				$callParams[$k] = is_string($v) ? urldecode($v) : $v;
			}
		}

		return $callParams;
	}

	public function resolveAndCall($callback, array $callParams = [], $call = true) {
		// 🔹 Lấy container từ Application hoặc fallback
		$app       = $this->funcs->getApplication();
		$container = $app ?? (\Illuminate\Foundation\Application::getInstance() ?? null);

		if (!$container) {
			throw new \RuntimeException('Container instance not found.');
		}

		// NEW: support Closure
		if ($callback instanceof \Closure) {
			return $call
				? $container->call($callback, $callParams)
				: function() use ($container, $callback, $callParams) {
					return $container->call($callback, $callParams);
				};
		}

		[$classOrInstance, $method] = $callback;

		// 🔹 Resolve instance controller
		$instance = is_object($classOrInstance)
			? $classOrInstance
			: $container->make($classOrInstance);

		// 🔹 Tự động inject FormRequest nếu có
		$reflection  = new \ReflectionMethod($instance, $method);
		$baseRequest = $container->bound('request')
			? $container->make('request')
			: \Illuminate\Http\Request::capture();

		foreach ($reflection->getParameters() as $param) {
			$type = $param->getType();
			if ($type && !$type->isBuiltin()) {
				$paramClass = $type->getName();

				// Inject FormRequest (nếu có)
				if (is_subclass_of($paramClass, \Illuminate\Foundation\Http\FormRequest::class)) {
					$formRequest = $paramClass::createFromBase($baseRequest);
					$formRequest->setContainer($container);
					$formRequest->setRedirector($container->make(\Illuminate\Routing\Redirector::class));
					if (method_exists($formRequest, 'validateResolved')) {
						$formRequest->validateResolved();
					}
					$container->instance($paramClass, $formRequest);
				}
			}
		}

		if (!$call) {
			// 🔹 Trả về callable đã resolve hoàn chỉnh và không call.
			return function() use ($container, $instance, $method, $callParams) {
				return $container->call([$instance, $method], $callParams);
			};
		}

		// 🔹 Gọi thông qua Container::call() để tự inject linh hoạt
		return $container->call([$instance, $method], $callParams);
	}

	public function resolveCallback($callback, array $callParams = []) {
		return $this->resolveAndCall($callback, $callParams, false);
	}

	public function prepareCallbackFunction($callbackFunction, $path, $fullPath, $requestPath = null) {
		$requestPath = $requestPath ?? trim($this->request->getRequestUri(), '/\\');
		$callParams  = $this->getCallParams($path, $fullPath, $requestPath, $this, $callbackFunction);
		return $this->resolveAndCall([$this, $callbackFunction], $callParams, false);
	}

	public function isLastMiddleware($currentClass, $allMiddlewares): bool {
		if (!is_array($allMiddlewares)) {
			return false;
		}

		// Lọc chỉ lấy key dạng số (0,1,2...)
		$middlewares = [];
		foreach ($allMiddlewares as $key => $value) {
			if (is_int($key)) {
				$middlewares[$key] = $value;
			}
		}

		if (empty($middlewares)) {
			return false;
		}

		// Lấy phần tử cuối cùng
		$last = end($middlewares);

		// dạng: [ 'ClassName', 'handle' ]
		if (is_array($last) && isset($last[0]) && $last[0] === $currentClass) {
			return true;
		}

		return false;
	}

}