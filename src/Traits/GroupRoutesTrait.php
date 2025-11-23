<?php

namespace WPSPCORE\Traits;

trait GroupRoutesTrait {

	public    $isForRouterMap      = false;

	public    $currentCallMethod   = null;

	private   $prefixStack         = [];
	private   $nameStack           = [];
	private   $middlewareStack     = [];
	private   $currentRouteName    = null;

	private   $callPrefixTimes     = 0;
	private   $callNameTimes       = 0;
	private   $callMiddlewareTimes = 0;
	private   $callGroupTimes      = 0;

	protected $namespace           = null;
	protected $version             = null;
	protected $defaultNamespace    = null;
	protected $defaultVersion      = null;

	private   $routeMapClassName   = null;

	/**
	 * Bật chế độ build route map
	 */
	public function initRouterMap() {
		$this->isForRouterMap = true;
		$this->initForRouterMap();
		$this->isForRouterMap = false;
		return $this;
	}

	/**
	 * Thêm prefix vào stack
	 */
	public function prefix($prefix) {
		$this->currentCallMethod = 'prefix';
		$this->callPrefixTimes++;

		$this->prefixStack[] = $prefix;
		return $this;
	}

	/**
	 * Thêm name vào stack hoặc đặt tên cho route
	 */
	public function name($name) {
		$this->currentCallMethod = 'name';
		$this->callNameTimes++;

		// Nếu có currentRouteName nhưng chưa được đặt tên — kiểm tra xem name này là prefix hay route
		if ($this->currentRouteName !== null) {
			// Nếu name chứa dấu '.' ở cuối => coi là group prefix, KHÔNG phải route
			if (substr($name, -1) == '.') {
				$this->nameStack[]      = $name;
				$this->currentRouteName = null; // reset route đang chờ đặt tên
			}
			else {
				// Là name route thực tế (vd: 'index', 'update')
				$fullName = $this->getCurrentName() . $name;
				$this->addToRouteMap($fullName);
				$this->currentRouteName = null;
			}
		}
		else {
			// Không có current route đang chờ => đây chắc chắn là group prefix
			$this->nameStack[]      = $name;
			$this->currentRouteName = null;
		}

		// Reset namespace cho Apis routes.
		if ($this->routeMapClassName == 'Apis') {
			$this->namespace($this->defaultNamespace ?? $this->funcs->_config('app.short_name'));
		}

		return $this;
	}

	/**
	 * Thêm namespace cho route Apis.
	 */
	public function namespace($namespace) {
		$this->namespace = $namespace;
		return $this;
	}

	/**
	 * Thêm version cho route Apis.
	 */
	public function version($version) {
		$this->version = $version;
		return $this;
	}

	/**
	 * Thêm middleware vào stack
	 */
	public function middleware($middlewares) {
		$this->currentCallMethod = 'middleware';
		$this->callMiddlewareTimes++;

		// Reset middleware stack first.
		$this->middlewareStack = []; // reset middleware

		// Chuẩn hóa middleware thành mảng
		if (!is_array($middlewares)) {
			$middlewares = [$middlewares];
		}

		// Chuẩn hóa mỗi middleware thành format [class, method]
		$normalized = [];
		foreach ($middlewares as $middleware) {
			if (is_string($middleware)) {
				if (strtolower($middleware) == 'or' || strtolower($middleware) == 'and') {
					$this->middlewareStack['relation'] = $middleware;
					continue;
				}
				// Nếu là string class name, thêm method mặc định 'handle'
				$normalized[] = [$middleware, 'handle'];
			}
			elseif (is_array($middleware)) {
				// Kiểm tra xem có phải là [class, method] hay không
				if (count($middleware) == 2 && is_string($middleware[0]) && is_string($middleware[1])) {
					// Đã đúng format [class, method]
					$normalized[] = $middleware;
				}
				elseif (isset($middleware[0]) && is_string($middleware[0])) {
					// Chỉ có class, không có method - thêm 'handle' mặc định
					$normalized[] = [$middleware[0], 'handle'];
				}
			}
		}

		// Chỉ thêm vào stack nếu có middleware hợp lệ
		if (!empty($normalized)) {
			$this->middlewareStack[] = $normalized;
		}

		return $this;
	}

	/**
	 * Nhóm các route lại với nhau
	 */
	public function group($callback, $middlewares = null) {
		$this->currentCallMethod = 'group';
		$this->callGroupTimes++;

		// Lưu số lượng middleware hiện tại trước khi vào group
		$middlewareCountBefore = count($this->middlewareStack);

		// Merge middleware nếu được truyền vào
		if ($middlewares !== null) {
			$this->middleware($middlewares);
		}

		// Chạy callback
		$callback();

		// Pop các stack sau khi group chạy xong
		$this->popStacks();

		// Đảm bảo pop tất cả middleware đã thêm trong group này
//		while (count($this->middlewareStack) >= $middlewareCountBefore) {
//			array_pop($this->middlewareStack);
//		}

		// Reset middleware if group() call lastest.
		$this->middlewareStack = [];

		return $this;
	}

	/**
	 * Pop các stack sau khi group kết thúc (KHÔNG pop middleware ở đây)
	 */
	protected function popStacks() {
		if (!empty($this->prefixStack) && count($this->prefixStack) >= $this->callGroupTimes) {
			array_pop($this->prefixStack);
		}

		if (!empty($this->nameStack) && count($this->nameStack) >= $this->callGroupTimes) {
			array_pop($this->nameStack);
			$this->callGroupTimes--;
		}

		array_pop($this->prefixStack);
	}

	/**
	 * Lấy prefix hiện tại từ stack
	 */
	protected function getCurrentPrefix() {
		return implode('/', array_filter($this->prefixStack));
	}

	/**
	 * Lấy name hiện tại từ stack
	 */
	protected function getCurrentName() {
		return implode('', array_filter($this->nameStack));
	}

	/**
	 * Merge tất cả middleware từ stack
	 */
	protected function getFlattenedMiddlewares() {
		$flattened = [];
		foreach ($this->middlewareStack as $key => $middlewares) {
			if (is_array($middlewares)) {
				$flattened = array_merge($flattened, $middlewares);
			}
			else {
				$flattened[$key] = $middlewares;
			}
		}
		return $flattened;
	}

	/**
	 * Build full path từ prefix stack và path hiện tại
	 */
	protected function buildFullPath($path) {
		$prefix = $this->getCurrentPrefix();
		if ($prefix) {
			return $prefix . '/' . ltrim($path, '/');
		}
		return $path;
	}

	/**
	 * Build full name từ name stack và name hiện tại
	 */
	protected function buildFullName($name) {
		$currentName = $this->getCurrentName();
		return $currentName . $name;
	}

	/**
	 * Đánh dấu route vừa tạo, chờ name()
	 */
	protected function markRouteForNaming($path) {
		$this->currentRouteName = null; // reset trước
		// Đảm bảo mỗi route có vùng nhớ riêng, không ghi đè lẫn nhau
		$this->currentRouteName = [
			'path'      => $this->buildFullPath($path),
			'timestamp' => microtime(true), // tránh đè khi tạo nhanh liên tiếp
		];
	}

	/**
	 * Thêm route vào map
	 */
	protected function addToRouteMap($fullName) {
		if ($this->isForRouterMap && $this->currentRouteName !== null) {
			$routeMap  = $this->funcs->getRouteMap();
			$className = (new \ReflectionClass($this))->getShortName();
			$this->routeMapClassName = $className;

			if (!isset($routeMap->map[$className])) {
				$routeMap->map[$className]     = [];
				$routeMap->mapIdea[$className] = [];
			}

			$routeMap->map[$className][$fullName]     = $this->currentRouteName['path'];
			$routeMap->mapIdea[$className][$fullName] = [
				'name'      => $fullName,
				'file'      => 'routes/' . $className . '.php',
				'line'      => (new \Exception())->getTrace()[1]['line'] ?? 0,
				'namespace' => $this->namespace ?? $this->defaultNamespace ?? $this->funcs->_getRootNamespace(),
				'version'   => $this->version ?: 'v1',
				'path'      => $this->currentRouteName['path'],
			];
//			$this->routeMap = $routeMap;
		}
	}

	/**
	 * Build call params as associative array so Container::call can autowire and inject properly.
	 */
	protected function getCallParams($path, $requestPath, $class, $method) {
		// Lấy container / request
		$app = $this->funcs->getApplication() ?? (\Illuminate\Foundation\Application::getInstance() ?? null);
		if (!$app) {
			throw new \RuntimeException('Container instance not found when building call params.');
		}
		$baseRequest = $app->bound('request') ? $app->make('request') : ($this->request ?? \Illuminate\Http\Request::capture());

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

}