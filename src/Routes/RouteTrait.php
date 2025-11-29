<?php

namespace WPSPCORE\Routes;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

trait RouteTrait {

	public function isPassedMiddleware($middlewares = null, $request = null, $args = []): bool {
		// Không có middleware -> pass
		if (empty($middlewares)) {
			return true;
		}

		// Lấy relation (AND/OR), mặc định AND
		$relation = 'AND';
		if (isset($middlewares['relation'])) {
			$relation = strtoupper((string)$middlewares['relation']);
			unset($middlewares['relation']);
		}

		// Chuẩn hoá middleware: mỗi item thành một "callable descriptor"
		// descriptor có thể là:
		// - ['type' => 'class', 'class' => ClassName, 'method' => 'handle']
		// - ['type' => 'closure', 'closure' => Closure]
		$normalized = [];
		foreach ($middlewares as $m) {
			if ($m instanceof \Closure) {
				$normalizedMiddleware = [
					'type' => 'closure',
					'closure' => $m
				];
				continue;
			}

			if (is_string($m)) {
				$normalizedMiddleware = [
					'type'   => 'class',
					'class'  => $m,
					'method' => 'handle',
				];
			}

			if (is_array($m)) {
				// [Class, method?] or nested structure
				if (isset($m[0]) && $m[0] instanceof \Closure) {
					// closure inside array
					$normalizedMiddleware = ['type' => 'closure', 'closure' => $m[0]];
				}
				elseif (isset($m[0]) && is_string($m[0])) {
					$method = isset($m[1]) && is_string($m[1]) ? $m[1] : 'handle';

					if (preg_match('/^(abilities:|ability:)(.*?)$/iu', $method, $matches)) {
						$ability_relation     = $matches[1] == 'abilities:' ? 'AND' : 'OR';
						$abilities            = explode(',', $matches[2]);
						$normalizedMiddleware = [
							'type'   => 'class',
							'class'  => $m[0],
							'method' => 'handle',
							'args'   => [
								'abilities'        => $abilities,
								'ability_relation' => $ability_relation,
							],
						];
					}
					else {
						$normalizedMiddleware = [
							'type'   => 'class',
							'class'  => $m[0],
							'method' => $method,
						];
					}
				}
			}

			if (isset($normalizedMiddleware)) {
				$normalizedMiddleware['args'] = array_merge($normalizedMiddleware['args'] ?? [], $args);
				$normalized[]                 = $normalizedMiddleware;
			}
		}

		// Lấy request & app
		$app     = $this->funcs->getApplication();
		$request = $app->make('request');

		/**
		 * -----------------
		 * Helper: chạy 1 middleware descriptor, trả về chuẩn
		 * -----------------
		 * ['ok' => bool, 'response' => Response|null]
		 */
		$runOne = function($normalizedMiddleware) use ($request, $app) {
			// $next giả: middleware gọi $next($request) => được coi là "pass" -> trả Response 200
			$next = function($req = null) {
				return new Response('', 200);
			};

			try {
				if ($normalizedMiddleware['type'] === 'closure') {
					$res = call_user_func($normalizedMiddleware['closure'], $request, $next);
				}
				elseif ($normalizedMiddleware['type'] === 'class') {
					$class  = $normalizedMiddleware['class'];
					$method = $normalizedMiddleware['method'] ?? 'handle';

					// nếu class không tồn tại, coi như fail
					if (!class_exists($class)) {
						return ['ok' => false, 'response' => null];
					}

					// 🚀 Quan trọng: dùng Container để tự động Dependency Injection
					try {
						$instance = $app->make($class);
					}
					catch (\Throwable $e) {
						return ['ok' => false, 'response' => null];
					}

					// nếu method không tồn tại, cố gọi handle, nếu không có -> fail
					if (!method_exists($instance, $method)) {
						if (method_exists($instance, 'handle')) {
							$res = $instance->handle($request, $next, $normalizedMiddleware['args'] ?? []);
						}
						else {
							return ['ok' => false, 'response' => null];
						}
					}
					else {
						$res = $instance->$method($request, $next, $normalizedMiddleware['args'] ?? []);
					}
				}
				else {
					return ['ok' => false, 'response' => null];
				}
			}
			catch (\Throwable $e) {
				// lỗi khi chạy middleware => coi là fail
				return ['ok' => false, 'response' => null];
			}

			// Chuẩn hóa kết quả:
			// - Nếu là Symfony Response (Illuminate Response kế thừa) -> check status
			// - Nếu là boolean true -> coi là pass
			// - Nếu là boolean false -> coi là fail
			// - Nếu là null -> coi là pass (nếu middleware gọi $next và không trả gì)
			if ($res instanceof Response) {
				$status = (int)$res->getStatusCode();
				return ['ok' => ($status < 400), 'response' => $res];
			}

			if (is_bool($res)) {
				return ['ok' => $res === true, 'response' => null];
			}

			if ($res === null) {
				// mặc định coi là pass (nhiều middleware PHP cũ không return, nhưng gọi $next internally)
				return ['ok' => true, 'response' => null];
			}

			// Trường hợp trả string/other -> coi là pass (hoặc bạn có thể đổi thành fail)
			return ['ok' => true, 'response' => null];
		};
		/**
		 * -----------------
		 */

		// Logic OR: chỉ cần 1 pass => pass toàn bộ
		if ($relation === 'OR') {
			foreach ($normalized as $normalizedMiddleware) {
				$r = $runOne($normalizedMiddleware);
				if ($r['ok'] === true) {
					return true; // pass sớm
				}
			}
			return false; // tất cả fail
		}

		// Logic AND: tất cả phải pass
		foreach ($normalized as $normalizedMiddleware) {
			$r = $runOne($normalizedMiddleware);
			if ($r['ok'] !== true) {
				return false; // có 1 fail -> fail ngay
			}
		}

		// Tất cả pass
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
			$class = null;
			$method = null;
		} else {
			$class = $callbackOrClass;
			$reflection = new \ReflectionMethod($class, $method);
		}

		// Match pattern: KHÔNG escape path vì path đã là regex pattern (có thể chứa (?P<name>...))
		// Nếu $path có ^ hoặc $ thì vẫn dùng như vậy; nếu không có, ta match toàn chuỗi.
		$regexPath = $this->funcs->_regexPath($path);
		$pattern = '#' . $regexPath . '#iu';

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

		$callParams['path'] = $path;
		$callParams['fullPath'] = $fullPath;
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
		$app = $this->funcs->getApplication();
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
		$reflection = new \ReflectionMethod($instance, $method);
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
		$callParams = $this->getCallParams($path, $fullPath, $requestPath, $this, $callbackFunction);
		return $this->resolveAndCall([$this, $callbackFunction], $callParams, false);
	}

}