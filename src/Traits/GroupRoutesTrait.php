<?php

namespace WPSPCORE\Traits;

trait GroupRoutesTrait {

	public  $isForRouterMap   = false;
	private $prefixStack      = [];
	private $nameStack        = [];
	private $middlewareStack  = [];
	private $currentRouteName = null;

	private $callPrefixTimes     = 0;
	private $callNameTimes       = 0;
	private $callMiddlewareTimes = 0;
	private $callGroupTimes      = 0;

	protected $namespace = null;
	protected $version   = null;

	protected $defaultNamespace = null;
	protected $defaultVersion   = null;

	private $routeMapClassName = null;

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
		$this->callPrefixTimes++;
		$this->prefixStack[] = $prefix;
		return $this;
	}

	/**
	 * Thêm name vào stack hoặc đặt tên cho route
	 */
	public function name($name) {
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
		$this->callGroupTimes++;

		// Lưu số lượng middleware hiện tại trước khi vào group
		$middlewareCountBefore = count($this->middlewareStack);

		// Merge middleware nếu được truyền vào
		if ($middlewares !== null) {
			$this->middleware($middlewares);
		}

		// Check middleware trước khi chạy group (chỉ khi không build map)
//		if (!$this->isForRouterMap) {
//			$allMiddlewares = $this->getFlattenedMiddlewares();
//			if (!$this->isPassedMiddleware($allMiddlewares, $this->request)) {
//				// Pop các stack và return nếu không pass middleware
//				$this->popStacks();
//				// Đảm bảo pop middleware đã thêm vào
//				while (count($this->middlewareStack) > $middlewareCountBefore) {
//					array_pop($this->middlewareStack);
//				}
//				return $this;
//			}
//		}

		// Chạy callback
		$callback();

		// Reset callGroupTimes.
		$this->callGroupTimes--;

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
		if (!empty($this->prefixStack) && count($this->prefixStack) > $this->callGroupTimes) {
			array_pop($this->prefixStack);
		}
		if (!empty($this->nameStack)) {
			array_pop($this->nameStack);
		}
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
		$app = $this->funcs->getApplication() ?? (\Illuminate\Foundation\Application::getInstance() ?? null);
		if (!$app) {
			throw new \RuntimeException('Container instance not found when building call params.');
		}

		$baseRequest = $app->bound('request') ? $app->make('request') : ($this->request ?? \Illuminate\Http\Request::capture());
		preg_match('/' . $this->funcs->_escapeRegex($path) . '$/iu', $requestPath, $matches);
		$named = array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);

		// Lấy các capture positional (1..n) — loại bỏ index 0 (full match)
		$positional = [];
		foreach ($matches as $k => $v) {
			if (is_int($k) && $k > 0) {
				$positional[] = $v;
			}
		}
		$posIndex = 0;

		$query = $baseRequest->query->all();
		$post  = $baseRequest->request->all();
		$attr  = $baseRequest->attributes->all();

		$reflection = new \ReflectionMethod($class, $method);
		$callParams = [];

		foreach ($reflection->getParameters() as $param) {
			$name  = $param->getName();
			$type  = $param->getType();
			$value = null;

			// Nếu param có type-hint class (VD: Request) → để Container tự inject
			if ($type && !$type->isBuiltin()) {
				continue;
			}

			// Ưu tiên theo tên param trong named match, attributes, post, query
			if (array_key_exists($name, $named)) {
				$value = $named[$name];
			}
			elseif (array_key_exists($name, $attr)) {
				$value = $attr[$name];
			}
			elseif (array_key_exists($name, $post)) {
				$value = $post[$name];
			}
			elseif (array_key_exists($name, $query)) {
				$value = $query[$name];
			}
			// Nếu không có giá trị theo tên thì lấy theo thứ tự positional capture
			elseif (isset($positional[$posIndex])) {
				$value = $positional[$posIndex];
				$posIndex++;
			}
			// Nếu không có positional thì dùng default (nếu có)
			elseif ($param->isDefaultValueAvailable()) {
				$value = $param->getDefaultValue();
			}
			else {
				$value = null;
			}

			$callParams[$name] = $value;
		}

		return $callParams;
	}

}