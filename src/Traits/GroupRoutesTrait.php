<?php

namespace WPSPCORE\Traits;

trait GroupRoutesTrait {

	public  $isForRouterMap   = false;
	private $prefixStack      = [];
	private $nameStack        = [];
	private $middlewareStack  = [];
	private $currentRouteName = null;

	/**
	 * Bật chế độ build route map
	 */
	public function withRouterMap() {
		$this->isForRouterMap = true;
		$this->initForRouterMap();
		return $this;
	}

	/**
	 * Thêm prefix vào stack
	 */
	public function prefix($prefix) {
		$this->prefixStack[] = $prefix;
		return $this;
	}

	/**
	 * Thêm name vào stack hoặc đặt tên cho route
	 */
	public function name($name) {
		// Nếu được gọi sau get() hoặc post(), đây là tên route cuối cùng
		if ($this->currentRouteName !== null) {
			$fullName = $this->getCurrentName() . $name;
			$this->addToRouteMap($fullName);
			$this->currentRouteName = null;
		}
		else {
			// Nếu không, đây là prefix name cho group
			$this->nameStack[] = $name;
		}
		return $this;
	}

	/**
	 * Thêm middleware vào stack
	 */
	public function middleware($middlewares) {
		// Chỉ thêm middleware nếu đang trong context của một route cụ thể
		// hoặc sẽ được thêm bởi group()
		$this->middlewareStack[] = is_array($middlewares) ? $middlewares : [$middlewares];
		return $this;
	}

	/**
	 * Nhóm các route lại với nhau
	 */
	public function group($callback, $middlewares = null) {
		// Lưu số lượng middleware hiện tại trước khi vào group
		$middlewareCountBefore = count($this->middlewareStack);

		// Merge middleware nếu được truyền vào
		if ($middlewares !== null) {
			$this->middleware($middlewares);
		}

		// Check middleware trước khi chạy group (chỉ khi không build map)
		if (!$this->isForRouterMap) {
			$allMiddlewares = $this->getFlattenedMiddlewares();
			if (!$this->isPassedMiddleware($allMiddlewares, $this->request)) {
				// Pop các stack và return nếu không pass middleware
				$this->popStacks();
				// Đảm bảo pop middleware đã thêm vào
				while (count($this->middlewareStack) > $middlewareCountBefore) {
					array_pop($this->middlewareStack);
				}
				return $this;
			}
		}

		// Chạy callback
		$callback();

		// Pop các stack sau khi group chạy xong
		$this->popStacks();

		// Đảm bảo pop tất cả middleware đã thêm trong group này
		while (count($this->middlewareStack) > $middlewareCountBefore) {
			array_pop($this->middlewareStack);
		}

		return $this;
	}

	/**
	 * Pop các stack sau khi group kết thúc (KHÔNG pop middleware ở đây)
	 */
	protected function popStacks() {
		if (!empty($this->prefixStack)) {
			array_pop($this->prefixStack);
		}
		if (!empty($this->nameStack)) {
			array_pop($this->nameStack);
		}
		// KHÔNG pop middleware ở đây vì sẽ được xử lý riêng trong group()
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
		foreach ($this->middlewareStack as $middlewares) {
			$flattened = array_merge($flattened, $middlewares);
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
		$this->currentRouteName = [
			'path' => $this->buildFullPath($path),
		];
	}

	/**
	 * Thêm route vào map
	 */
	protected function addToRouteMap($fullName) {
		if ($this->isForRouterMap && $this->currentRouteName !== null) {
			$mapRoutes = $this->mapRoutes;
			$className = (new \ReflectionClass($this))->getShortName();

			if (!isset($mapRoutes->map[$className])) {
				$mapRoutes->map[$className] = [];
			}

			$mapRoutes->map[$className][$fullName] = $this->currentRouteName['path'];
		}
	}

}