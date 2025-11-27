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

	public static function getCallParams($path, $fullPath, $requestPath, $class, $method, $args = []): array {
//		if (preg_match('/(\(.*?\))/iu', $path)) {
//			$path = str_replace('/', '\/', $path);
//		}

		// Match pattern: KHÔNG escape path vì path đã là regex pattern (có thể chứa (?P<name>...))
		// Nếu $path có ^ hoặc $ thì vẫn dùng như vậy; nếu không có, ta match toàn chuỗi.
//		$pattern = '/' . $path . '/iu';
		$regexPath = static::convertPathToRegex($path);
		$pattern = '#' . $regexPath . '#iu';

		$passed = false;

		// Nếu nơi gọi hàm này là route "Ajaxs" với method POST, check action và match path.
		if (preg_match('/Ajaxs$/', static::class)) {
			$httpMethod = static::$request->getMethod();
			if ($httpMethod === 'POST') {
				$params = static::$request->all();
				$passed = isset($params['action']) && $params['action'] === $path;
			}
		}

		// Kiểm tra path có khớp với request path hiện tại không?
		if (preg_match($pattern, $requestPath, $matches)) {
			$passed = true;
		}

		if (!$passed) {
			// Build all params as null for primitive args
			$reflection = new \ReflectionMethod($class, $method);
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
		$app = static::$funcs->getApplication();
		if (!$app) {
			throw new \RuntimeException('Container instance not found when building call params.');
		}
		$baseRequest = $app->bound('request') ? $app->make('request') : (static::$request ?? Request::capture());

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

	public static function resolveAndCall($callback, array $routeParams = []) {
		// 🔹 Lấy container từ Application hoặc fallback
		$app = static::$funcs->getApplication();
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

		// 🔹 Gọi thông qua Container::call() để tự inject linh hoạt
		return $container->call([$instance, $method], $routeParams);
	}

	/*
	 * 
	 */

	protected static function convertPathToRegex(string $path): string {
		return preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
	}

	public static function prepareCallbackFunction($callbackFunction, $path, $fullPath, $requestPath = null) {
		$requestPath = $requestPath ?? trim(static::$request->getRequestUri(), '/\\');
		$callParams = static::getCallParams($path, $fullPath, $requestPath, static::class, $callbackFunction);
		return static::resolveAndCall([static::class, $callbackFunction], $callParams);
	}

}