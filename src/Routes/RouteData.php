<?php

namespace WPSPCORE\Routes;

class RouteData {

	public         $funcs;
	public ?string $type        = null;     // Loại route.
	public ?string $route       = null;     // Class của Route trong WPSP: \WPSP\App\Instances\Routes\Apis
	public ?string $parentRoute = null;     // Class cha của Route trong WPSPCORE: \WPSPCORE\Routes\Apis\Apis
	public ?string $method      = null;     // HTTP method (GET, POST, ...)
	public ?string $path        = null;     // Path của route
	public ?string $fullPath    = null;     // Full path sau khi áp dụng prefix
	public ?string $namespace   = null;
	public ?string $version     = null;
	public         $callback    = null;     // Controller action hoặc Closure
	public array   $args        = [];
	public ?string $name        = null;     // Tên route đầy đủ sau khi gọi ->name()
	public array   $middlewares = [];       // Danh sách middleware áp dụng cho route

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
	public function __construct(
		string $type,
		string $route,
		string $method,
		string $path,
		$callback,
		array $args,
		array $groupAttributes,
		$funcs = null
	) {

		// Lấy prefix từ group, chuẩn hoá: đảm bảo luôn kết thúc bằng '/'
		$prefix = $groupAttributes['prefix'] ?? '';
		if ($prefix !== '') {
			$prefix = rtrim($prefix, '/') . '/';
		}

		// Gán thông tin cơ bản
		$this->type        = $type;
		$this->route       = $route;
		$this->parentRoute = get_parent_class($route);
		$this->method      = $method;
		$this->path        = ltrim($path, '/');
		$this->fullPath    = $prefix . $this->path;
		$this->callback    = $callback;
		$this->namespace   = $groupAttributes['namespace'] ?? null;
		$this->version     = $groupAttributes['version'] ?? null;
		$this->args        = $args;
		$this->funcs       = $funcs;


		// Gộp middleware từ group (unique để tránh lặp)
		$this->middlewares = isset($groupAttributes['middlewares'])
			? $this->prepareMiddlewaresFromGroup($groupAttributes['middlewares'])
			: [];
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
	public function name(string $name): RouteData {

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
	public function middleware($middlewares): RouteData {

		// đảm bảo là array
		$middlewares = is_array($middlewares) ? $middlewares : [$middlewares];

		$result = $this->middlewares ?: [];

		foreach ($middlewares as $key => $middleware) {

			// Giữ nguyên relation
			if ($key == 'relation') {
				$result['relation'] = $middleware;
				continue;
			}

			// Chuẩn hóa middleware
			$normalized = $this->normalizeMiddleware($middleware);

			// Format output: mỗi middleware thành: [ [class, method] ]
			$result[] = [$normalized];
		}

		$this->middlewares = $result;

		return $this;
	}


	/**
	 * Gán namespace cho route.
	 * Ví dụ:
	 * Route::namespace('wpsp')->group(...)
	 * Route::namespace('wpsp')->get(...)
	 * → namespace = 'wpsp'
	 */
	public function namespace($value): RouteData {
		$this->namespace = $value;
		return $this;
	}

	/**
	 * Gán version cho route.
	 * Ví dụ:
	 * Route::version('v1')->group(...)
	 * Route::version('v1')->get(...)
	 * → version = 'v1'
	 */
	public function version($value): RouteData {
		$this->version = $value;
		return $this;
	}

	/*
	 *
	 */

	/**
	 * Được gọi từ AjaxsRoute để gắn stack prefix name
	 * (stack này được build từ các group cha)
	 *
	 * @param array $stack
	 */
	public function setGroupNameStack(array $stack): void {
		$this->nameStack = $stack;
	}

	/**
	 * Chuẩn hoá + loại trùng middleware từ group, giữ nguyên 'relation' key.
	 *
	 * - Nếu middleware là string (class) -> method = 'handle'
	 * - Nếu middleware là ['Class', 'method'] -> giữ, nếu thiếu method -> 'handle'
	 * - Loại trùng bằng serialize để so sánh mảng đa chiều
	 * - Trả về mảng với 'relation' (nếu có) và các numeric keys bắt đầu từ 1
	 *
	 * @param array $middlewaresRaw
	 *
	 * @return array
	 */
	private function prepareMiddlewaresFromGroup(array $middlewaresRaw): array {
		$relation = null;
		$items    = [];

		// Nếu người dùng có truyền 'relation' với key chuỗi, tách ra
		if (array_key_exists('relation', $middlewaresRaw)) {
			$relation = $middlewaresRaw['relation'];
			// bỏ key relation để khỏi xử lý như 1 middleware
			unset($middlewaresRaw['relation']);
		}

		// Có thể người dùng truyền relation như phần tử đầu (không key) — xử lý thêm:
		// nếu phần tử 0 là string 'OR' hoặc 'AND' và có vẻ là relation, giữ lại.
		// (Chỉ thực hiện nếu key 'relation' không tồn tại)
		if ($relation === null && isset($middlewaresRaw[0]) && is_string($middlewaresRaw[0])) {
			$maybe = strtoupper($middlewaresRaw[0]);
			if ($maybe === 'OR' || $maybe === 'AND') {
				$relation = $middlewaresRaw[0];
				unset($middlewaresRaw[0]);
			}
		}

		// Chuẩn hoá từng middleware còn lại
		foreach ($middlewaresRaw as $mw) {
			// Nếu người ta truyền group middleware theo dạng nested (ví dụ: [[Class,method]])
			// hoặc đơn lẻ, xử lý đều được.
			$normalized = $this->normalizeMiddleware($mw);
			$items[]    = $normalized;
		}

		// Loại trùng (deep) — giữ thứ tự xuất hiện
		$uniqueItems = [];
		$seen        = [];
		foreach ($items as $it) {
			$key = serialize($it);
			if (!isset($seen[$key])) {
				$seen[$key]    = true;
				$uniqueItems[] = $it;
			}
		}

		// Reindex numeric keys bắt đầu từ 1 (theo mong muốn)
		$result = [];
		if ($relation !== null) {
			$result['relation'] = $relation;
		}
		$idx = 1;
		foreach ($uniqueItems as $ui) {
			$result[$idx] = $ui;
			$idx++;
		}

		return $result;
	}

	/**
	 * Normalize một middleware entry thành [ClassString, methodString]
	 * - Nếu truyền string -> ['ClassName', 'handle']
	 * - Nếu truyền ['Class', 'method'] -> đảm bảo method có, nếu không có -> 'handle'
	 */
	private function normalizeMiddleware($middleware) {
		// Case: class string → auto gán method handle
		if (is_string($middleware)) {
			return [$middleware, 'handle'];
		}

		// Case: array => có thể là ['Class','method'] hoặc [[...]] (chỉ lấy phần tử đầu nếu là array-of-array)
		if (is_array($middleware)) {
			// Nếu người ta truyền nested array như [[Class,method]] (1 phần tử mảng)
			if (count($middleware) === 1 && is_array($middleware[0])) {
				$middleware = $middleware[0];
			}

			// Nếu là associative like ['relation' => 'OR'] thì bỏ (không phải middleware)
			if (array_key_exists('relation', $middleware)) {
				// Không xử lý ở đây; caller đã tách relation trước rồi
				return $middleware;
			}

			// Nếu truyền chỉ class tại index 0
			if (isset($middleware[0]) && is_string($middleware[0])) {
				if (!isset($middleware[1]) || $middleware[1] === null) {
					$middleware[1] = 'handle';
				}
				return [$middleware[0], $middleware[1]];
			}

			// Nếu mảng khác (không chuẩn) — trả về như fallback (serialize sẽ giúp loại trùng)
			return $middleware;
		}

		// fallback: trả nguyên giá trị
		return $middleware;
	}

}
