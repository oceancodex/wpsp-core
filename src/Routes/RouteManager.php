<?php

namespace WPSPCORE\Routes;

class RouteManager {

	/**
	 * Danh sách toàn bộ route đã được tạo.
	 * Mỗi phần tử là một đối tượng RouteData.
	 */
	private static array $routes = [];

	/**
	 * Stack chứa các group attributes (prefix, name, middlewares)
	 * được push/pop trong quá trình xử lý group().
	 *
	 * Cơ chế giống Laravel:
	 * - Mỗi lần vào group(), push attributes
	 * - Khi thoát group(), pop attributes
	 * - Dồn tất cả attributes của các group lại cho route con
	 */
	private static array $groupStack = [];

	/**
	 * Push một group attribute mới vào stack.
	 *
	 * Ví dụ:
	 *   Route::prefix('api')->middleware(...)->group(...)
	 *
	 * → groupStack sẽ lưu:
	 *   [
	 *       'prefix' => 'api',
	 *       'name' => ...,
	 *       'middlewares' => [...],
	 *   ]
	 */
	public static function pushGroupAttributes(array $attrs) {

		// Chuẩn hóa giá trị để đảm bảo đủ key prefix/name/middlewares
		$attrs = [
			'prefix'      => $attrs['prefix'] ?? '',
			'name'        => $attrs['name'] ?? '',
			'middlewares' => $attrs['middlewares'] ?? [],
		];

		// Push vào stack
		self::$groupStack[] = $attrs;
	}

	/**
	 * Pop group attribute cuối cùng khỏi stack.
	 * Gọi khi kết thúc một group().
	 */
	public static function popGroupAttributes() {
		array_pop(self::$groupStack);
	}

	/**
	 * Lấy toàn bộ prefix, name, middleware đã merge từ tất cả group cha.
	 * Cơ chế giống Laravel: group cha luôn bao group con.
	 *
	 * Kết quả hợp nhất có dạng:
	 * [
	 *     'prefix' => 'api/v1/',
	 *     'name' => 'admin.user.',
	 *     'middlewares' => [...],
	 * ]
	 */
	public static function currentGroupAttributes(): array {

		// Khởi tạo giá trị trống
		$merged = ['prefix' => '', 'name' => '', 'middlewares' => []];

		// Lần lượt merge từ group bên ngoài → group vào trong
		foreach (self::$groupStack as $g) {

			/**
			 * Merge prefix:
			 * - loại bỏ slash thừa
			 * - ghép prefix chính xác
			 */
			if (!empty($g['prefix'])) {

				$prefix = rtrim($g['prefix'], '/');

				if ($prefix !== '') {
					// Ghép prefix cha + prefix con
					$merged['prefix'] =
						rtrim($merged['prefix'], '/') . '/' . ltrim($prefix, '/');

					// Chuẩn hóa: bỏ slash đầu/cuối
					$merged['prefix'] = trim($merged['prefix'], '/');

					// Nếu có prefix → thêm slash cho đúng chuẩn
					if ($merged['prefix'] !== '') {
						$merged['prefix'] .= '/';
					}
				}
			}

			/**
			 * Merge route name prefix
			 * Ví dụ:
			 *   group cha: admin.
			 *   group con: user.
			 *   → admin.user.
			 */
			if (!empty($g['name'])) {
				$merged['name'] .= $g['name'];
			}

			/**
			 * Merge middleware (stack)
			 */
			if (!empty($g['middlewares'])) {
				$merged['middlewares'] = array_merge(
					$merged['middlewares'],
					$g['middlewares']
				);
			}
		}

		// Đảm bảo prefix phải kết thúc bằng '/'
		if ($merged['prefix'] !== '' && substr($merged['prefix'], -1) !== '/') {
			$merged['prefix'] .= '/';
		}

		return $merged;
	}

	/**
	 * Lưu một route vào danh sách tất cả routes.
	 * Route được truyền vào là những đối tượng RouteData đã hoàn chỉnh.
	 */
	public static function addRoute(RouteData $route) {
		self::$routes[] = $route;
	}

	/**
	 * Lấy toàn bộ route đã tạo.
	 */
	public static function all(): array {
		return self::$routes;
	}

}
