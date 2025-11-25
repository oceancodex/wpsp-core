<?php

namespace WPSPCORE\Traits;

trait GroupRoutesTrait_old {

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
	private   $callRouteTimes      = 0;

	protected $namespace           = null;
	protected $version             = null;
	protected $defaultNamespace    = null;
	protected $defaultVersion      = null;

	private   $routeMap            = null;
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
//		$this->middlewareStack = [];

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

		// Merge middleware nếu được truyền vào
		if ($middlewares !== null) {
			$this->middleware($middlewares);
		}

		// Chạy callback
		$callback();

		// Pop các stack sau khi group chạy xong
		$this->popStacks();

		// Reset middleware if group() call lastest.
		$this->middlewareStack = [];

		$this->callMiddlewareTimes--;

		return $this;
	}

	/**
	 * Pop các stack sau khi group kết thúc (KHÔNG pop middleware ở đây)
	 */
	protected function popStacks() {
		if (!empty($this->nameStack) && count($this->nameStack) >= $this->callGroupTimes) {
			array_pop($this->nameStack);
		}

		array_pop($this->prefixStack);

		$this->callGroupTimes--;
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
			$this->routeMap = $routeMap;
		}
	}

}