<?php

namespace WPSPCORE\Routes;

class RouteData {

	public $type;

	public $method;        // HTTP method (GET, POST, ...)
	public $path;          // Full path sau khi áp dụng prefix
	public $callback;      // Controller action hoặc Closure

	public $name        = null;   // Tên route đầy đủ sau khi gọi ->name()
	public $middlewares = [];     // Danh sách middleware áp dụng cho route

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
	public function __construct(string $type, string $method, string $path, $callback, array $groupAttributes) {

		// Lấy prefix từ group, chuẩn hoá: đảm bảo luôn kết thúc bằng '/'
		$prefix = $groupAttributes['prefix'] ?? '';
		if ($prefix !== '') {
			$prefix = rtrim($prefix, '/') . '/';
		}

		// Gán thông tin cơ bản
		$this->type     = $type;
		$this->method   = $method;
		$this->path     = $prefix . ltrim($path, '/'); // ghép prefix + uri
		$this->callback = $callback;

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

		// Ghép toàn bộ prefix name từ stack (theo đúng cơ chế Laravel)
		$prefix = implode('', $this->nameStack ?? []);

		// Gán name hoàn chỉnh
		$this->name = $prefix . $name;

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
