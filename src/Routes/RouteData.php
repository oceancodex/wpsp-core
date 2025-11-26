<?php

namespace WPSPCORE\Routes;

class RouteData {

	public $funcs;

	public $type;
	public $route;

	public $method;                 // HTTP method (GET, POST, ...)
	public $path;                   // Path của route
	public $fullPath;               // Full path sau khi áp dụng prefix
	public $callback;               // Controller action hoặc Closure

	public $name        = null;     // Tên route đầy đủ sau khi gọi ->name()
	public $middlewares = [];       // Danh sách middleware áp dụng cho route

	/**
	 * Lưu stack các tên group (name prefix) theo thứ tự.
	 * Ví dụ:
	 *   Route::name('admin.')->group(...)
	 *   Route::name('user.')->group(...)
	 * thì nameStack = ['admin.', 'user.']
	 */
	protected array $nameStack = [];

	/**
	 * Khởi tạo route data
	 *
	 * @param string $method          HTTP method
	 * @param string $path            Đường dẫn gốc (chưa có prefix)
	 * @param mixed  $callback        Controller + method hoặc Closure
	 * @param array  $groupAttributes Các thuộc tính gộp từ tất cả group (prefix, name, middleware)
	 */
	public function __construct(string $type, string $route, string $method, string $path, $callback, array $groupAttributes, $funcs = null) {

		// Lấy prefix từ group, chuẩn hoá: đảm bảo luôn kết thúc bằng '/'
		$prefix = $groupAttributes['prefix'] ?? '';
		if ($prefix !== '') {
			$prefix = rtrim($prefix, '/') . '/';
		}

		// Gán thông tin cơ bản
		$this->type     = $type;
		$this->route    = $route;
		$this->method   = $method;
		$this->path     = ltrim($path, '/');
		$this->fullPath = $prefix . $this->path;
		$this->callback = $callback;
		$this->funcs    = $funcs;

		// Gộp middleware từ group (unique để tránh lặp)
		$this->middlewares = isset($groupAttributes['middlewares'])
			? array_values(array_unique($groupAttributes['middlewares']))
			: [];
	}

	/**
	 * Được gọi từ AjaxsRoute để gắn stack prefix name
	 * (stack này được build từ các group cha)
	 *
	 * @param array $stack
	 */
	public function setGroupNameStack(array $stack) {
		$this->nameStack = $stack;
	}

	/**
	 * Định nghĩa tên route.
	 * Ví dụ:
	 *   Route::name('admin.')->group(...)
	 *   → nameStack = ['admin.']
	 *
	 * Khi người dùng gọi:
	 *   Route::get(...)->name('index')
	 *
	 * Thì name = "admin.index"
	 */
	public function name(string $name) {

		// Ghép toàn bộ prefix name từ stack.
		$prefix = implode('', $this->nameStack ?? []);

		// Gán name hoàn chỉnh.
		$this->name = $prefix . $name;

		// Add route map khi có name.
		$this->funcs->getRouteMap()->add($this);

		return $this;
	}

	/**
	 * Thêm middleware trực tiếp vào route
	 * Ví dụ:
	 *   ->middleware(Auth::class)
	 *
	 * Middleware từ group đã có sẵn từ constructor,
	 * phương thức này bổ sung thêm middleware mức route.
	 */
	public function middleware($middlewares) {

		// Bảo đảm luôn convert thành array
		$middlewares = is_array($middlewares) ? $middlewares : [$middlewares];

		// Gộp + loại trùng
		$this->middlewares = array_values(array_unique(
			array_merge($this->middlewares, $middlewares)
		));

		return $this;
	}

}
