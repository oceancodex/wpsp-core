<?php

namespace WPSPCORE\Base;

use Illuminate\Http\Request;
use WPSPCORE\Traits\BaseInstancesTrait;

abstract class BaseInstances {

	use BaseInstancesTrait;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		$this->baseInstanceConstruct($mainPath, $rootNamespace, $prefixEnv, $extraParams);
	}

	/*
	 *
	 */

	public function __set($name, $value) {
		$this->{$name} = $value;
	}

	public function __get($name) {
		return $this->{$name} ?? null;
	}

	/*
	 *
	 */

	protected function getCallParams($path, $requestPath, $class, $method): array {
		// Lấy container / request
		$app = $this->funcs->getApplication();
		if (!$app) {
			throw new \RuntimeException('Container instance not found when building call params.');
		}
		$baseRequest = $app->bound('request') ? $app->make('request') : ($this->request ?? Request::capture());

		// Chuẩn hóa requestPath: loại bỏ query string, trim
//		$requestPath = preg_replace('/\?.*$/', '', $requestPath);
//		$requestPath = trim($requestPath, '/\\');

		// Match pattern: KHÔNG escape path vì path đã là regex pattern (có thể chứa (?P<name>...))
		// Nếu $path có ^ hoặc $ thì vẫn dùng như vậy; nếu không có, ta match toàn chuỗi.
		$pattern = '/' . $path . '/iu';

		if (!preg_match($pattern, $requestPath, $matches)) {
			return []; // Không match => không param
		}

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
		$reflection = new \ReflectionMethod($class, $method);
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
		$callParams['requestPath'] = $requestPath;

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

	protected function resolveAndCall($callback, array $routeParams = []) {
		// 🔹 Lấy container Laravel từ Application hoặc fallback
		$app = $this->funcs->getApplication();
		$container = $app ?? (\Illuminate\Foundation\Application::getInstance() ?? null);

		if (!$container) {
			throw new \RuntimeException('Container instance not found.');
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

		// 🔹 Gọi thông qua Container::call() để Laravel tự inject linh hoạt
		return $container->call([$instance, $method], $routeParams);
	}

	/*
	 *
	 */

	protected function prepareCallbackFunction($callbackFunction, $path, $requestPath = null) {
		$requestPath = $requestPath ?? trim($this->request->getRequestUri(), '/\\');
		$callParams = $this->getCallParams($path, $requestPath, $this, $callbackFunction);
		return $this->resolveAndCall([$this, $callbackFunction], $callParams);
	}

}