<?php

namespace WPSPCORE\App\Routes;

use WPSPCORE\BaseInstances;

class RouteManager extends BaseInstances {

	/**
	 * Danh sách toàn bộ route đã được tạo.
	 * Mỗi phần tử là một đối tượng RouteData.
	 */
	private $routes = [];

	/**
	 * Stack chứa các group attributes (prefix, name, middlewares)\
	 * được push/pop trong quá trình xử lý group().
	 *
	 * Cơ chế:
	 * - Mỗi lần vào group(), push attributes
	 * - Khi thoát group(), pop attributes
	 * - Dồn tất cả attributes của các group lại cho route con
	 */
	private $groupStack = [];

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
	public function pushGroupAttributes($attrs) {

		// Chuẩn hóa giá trị để đảm bảo đủ key prefix/name/middlewares
		$attrs = [
			'prefix'      => $attrs['prefix'] ?? '',
			'name'        => $attrs['name'] ?? '',
			'middlewares' => $attrs['middlewares'] ?? [],
			'namespace'   => $attrs['namespace'] ?? null,
			'version'     => $attrs['version'] ?? null,
		];

		// Push vào stack
		$this->groupStack[] = $attrs;
	}

	/**
	 * Pop group attribute cuối cùng khỏi stack.
	 * Gọi khi kết thúc một group().
	 */
	public function popGroupAttributes() {
		array_pop($this->groupStack);
	}

	/**
	 * Lấy toàn bộ prefix, name, middleware đã merge từ tất cả group cha.\
	 * Cơ chế: group cha luôn bao group con.
	 *
	 * Kết quả hợp nhất có dạng:
	 * [
	 *     'prefix' => 'api/v1/',
	 *     'name' => 'admin.user.',
	 *     'middlewares' => [...],
	 * ]
	 */
	public function currentGroupAttributes($type = null) {
		// Khởi tạo giá trị trống
		$merged = [
			'prefix'      => '',
			'name'        => '',
			'middlewares' => [],
			'namespace'   => $type !== 'Apis' ? ($this->funcs->_getRootNamespace() ?? null) : null,
			'version'     => null,
		];

		// Lần lượt merge từ group bên ngoài → group vào trong
		foreach ($this->groupStack as $g) {

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

			/**
			 * Merge namespace (override).
			 */
			if (!empty($g['namespace'])) {
				$merged['namespace'] = $g['namespace'];
			}

			/**
			 * Merge version (override).
			 */
			if (!empty($g['version'])) {
				$merged['version'] = $g['version'];
			}
		}

		// Đảm bảo prefix phải kết thúc bằng '/'
		if ($merged['prefix'] !== '' && substr($merged['prefix'], -1) !== '/') {
			$merged['prefix'] .= '/';
		}

		return $merged;
	}

	/**
	 * Lưu một route vào danh sách tất cả routes.\
	 * Route được truyền vào là những đối tượng RouteData đã hoàn chỉnh.
	 */
	public function addRoute(RouteData $route) {
		$this->routes[] = $route;
	}

	/**
	 * Lấy toàn bộ route đã tạo.
	 */
	public function all() {
		return $this->routes;
//		return array_map(function($route) {
//			unset($route->funcs);
//			return $route;
//		}, $this->routes);
	}

	/**
	 * Chạy tất cả các route đã tạo.
	 */
	public function executeAllRoutes() {
		foreach ($this->routes as $routeItem) {
			$type        = $routeItem->type;
			$route       = $routeItem->route;
//			$parentRoute = '\\' . trim($routeItem->parentRoute, '\\');
			$method      = $routeItem->method;
//			$path        = $routeItem->path;
//			$fullPath    = $routeItem->fullPath;
//			$callback    = $routeItem->callback;
//			$args        = $routeItem->args;
//			$name        = $routeItem->name;
//			$middlewares = $routeItem->middlewares;

			/**
			 * Nếu route là Actions hoặc Filters thì method sẽ là "action" và "filter".\
			 * Như thế sẽ chạy vào hook() thay vì execute() => Sai\
			 * Vì vậy cần phải lọc điều kiện "type" để loại trừ việc Actions và Filter chạy hook().\
			 * Actions và Filters cần phải chạy phương thức execute() tương tự các route khác.
			 */
			if ($type !== 'Actions' && $type !== 'Filters' && ($method == 'action' || $method == 'filter')) {
				$route::instance()->hook($routeItem);
			}
			elseif ($method == 'remove_action' || $method == 'remove_filter') {
				$route::instance()->remove_hook($routeItem);
			}
			else {
				$route::instance()->execute($routeItem);
			}
		}
	}

}
