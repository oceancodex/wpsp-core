<?php

namespace WPSPCORE\Routes;

use WPSPCORE\Base\BaseInstances;

/**
 * Hỗ trợ gọi động: prefix(), name(), middleware(), group(),
 * và các HTTP verb (get/post/put/patch/delete/options)
 *
 * @method $this name(string $name)
 * @method $this group($callback)
 * @method $this prefix(string $prefix)
 * @method $this middleware(array|string $middlewares)
 */
abstract class BaseRoute extends BaseInstances {

	use BaseRouteTrait;

	/**
	 * Lưu các giá trị được gọi trước khi gọi HTTP verb
	 * Ví dụ:
	 *     Route::prefix('abc')->middleware(XYZ::class)->get(...)
	 *
	 * Các giá trị prefix / name / middlewares sẽ được lưu vào đây trước.
	 */
	protected static array $pending = [];

	/**
	 * Stack dùng để lưu các prefix name của group.
	 * Ví dụ:
	 *     Route::name('admin.')->group(function() {
	 *         Route::name('user.')->group(function() {
	 *             Route::get('list')->name('index');
	 *         });
	 *     });
	 *
	 * nameStack khi chạy route "list" sẽ là:
	 *     ['admin.', 'user.']
	 */
	protected static array $nameStack = [];

	/*
	 *
	 */

	/**
	 * Nếu gọi method thông thường (non-static) → chuyển sang static handler
	 */
	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	/**
	 * Xử lý tất cả method động.
	 */
	public static function __callStatic($method, $arguments) {
		$method = strtolower($method);

		/**
		 * 1) Nếu gọi prefix(), name(), middleware()
		 * → chỉ lưu vào pending, chưa tạo route
		 */
		if (in_array($method, ['prefix', 'name', 'middleware'])) {

			// middleware có thể là array hoặc string
			if ($method === 'middleware') {
				static::$pending['middlewares'] = is_array($arguments[0])
					? $arguments[0]
					: [$arguments[0]];
			}
			else {
				// prefix hoặc name
				static::$pending[$method] = $arguments[0];
			}

			// trả về new static để chain tiếp
			return new static();
		}

		/**
		 * 2) Xử lý group()
		 * → tạo phạm vi group và áp dụng prefix/name/middleware cho các route con
		 */
		elseif ($method === 'group') {

			// Lấy toàn bộ giá trị pending trước group
			$attrs = [
				'prefix'      => static::$pending['prefix'] ?? '',
				'name'        => static::$pending['name'] ?? '',
				'middlewares' => static::$pending['middlewares'] ?? [],
			];

			// Nếu group có khai báo name() → push vào nameStack.
			if (!empty($attrs['name'])) {
				static::$nameStack[] = $attrs['name'];
			}

			// Push toàn bộ thuộc tính group vào RouteManager
			static::$funcs->getRouteManager()::pushGroupAttributes($attrs);

			// reset pending để không ảnh hưởng route khác
			static::$pending = [];

			// chạy callback group (tạo route con)
			$callback = $arguments[0];
			$callback();

			// Sau khi group kết thúc → remove prefix name
			if (!empty($attrs['name'])) {
				array_pop(static::$nameStack);
			}

			// pop group attributes khỏi stack
			static::$funcs->getRouteManager()::popGroupAttributes();

			return new static;
		}

		/**
		 * 3) Xử lý HTTP verbs (get/post/put/patch/delete/options)
		 * Đây là lúc route thực sự được tạo.
		 */
		return static::buildRoute($method, $arguments);
	}

	/*
	 *
	 */

	/**
	 * Tạo đối tượng RouteData và lưu vào RouteManager.
	 */
	public static function buildRoute($method, $arguments): RouteData {
		$path     = $arguments[0];
		$callback = $arguments[1] ?? null;
		$args     = $arguments[2] ?? [];

		// Lấy attributes của tất cả group đang active
		$group = static::$funcs->getRouteManager()::currentGroupAttributes();

		/**
		 * Hợp nhất prefix tạm (chỉ có tác dụng cho route này)
		 * Ví dụ:
		 *     Route::prefix('x')->get(...)
		 */
		if (!empty(static::$pending['prefix'])) {
			$group['prefix'] .= rtrim(static::$pending['prefix'], '/') . '/';
		}

		/**
		 * Hợp nhất name tạm
		 * Ví dụ:
		 *     Route::name('x.')->get(...)
		 */
		if (!empty(static::$pending['name'])) {
			$group['name'] .= static::$pending['name'];
		}

		/**
		 * Hợp nhất middleware tạm
		 */
		if (!empty(static::$pending['middlewares'])) {
			$group['middlewares'] = array_values(array_unique(array_merge(
				$group['middlewares'],
				static::$pending['middlewares']
			)));
		}

		/**
		 * 4) Tạo đối tượng RouteData
		 * RouteData sẽ giữ method, path, callback, prefix, middlewares
		 */
		$routeClass = static::class;
		$parentRouteClass = get_parent_class($routeClass);
		$type  = basename(str_replace('\\', '/', $routeClass));
		$route = new RouteData(
			$type,
			$routeClass,
			$parentRouteClass,
			$method,
			$path,
			$callback,
			array_merge($args, ['route' => '123']),
			$group,
			static::$funcs
		);

		/**
		 * Gắn nameStack hiện tại vào route
		 * Khi người dùng gọi ->name('abc') thì RouteData sẽ dùng nameStack để build full route name.
		 */
		$route->setGroupNameStack(static::$nameStack);

		// Lưu route vào RouteManager.
		static::$funcs->getRouteManager()::addRoute($route);

		// Reset pending sau khi tạo route.
		static::$pending = [];

		return $route;
	}

}
