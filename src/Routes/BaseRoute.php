<?php

namespace WPSPCORE\Routes;

/**
 * Hỗ trợ gọi động: prefix(), name(), middleware(), group(),
 * và các HTTP verb (get/post/put/patch/delete/options)
 *
 * @method $this middleware(array|string $middlewares)
 * @method $this group($callback)
 * @method $this name(string $name)
 * @method $this prefix(string $prefix)
 */
abstract class BaseRoute {

	/**
	 * Lưu các giá trị được gọi trước khi gọi HTTP verb
	 * Ví dụ:
	 *     Route::prefix('abc')->middleware(XYZ::class)->get(...)
	 *
	 * Các giá trị prefix / name / middlewares sẽ được lưu vào đây trước.
	 */
	protected static array $pending = [];

	/**
	 * Stack dùng để lưu các prefix name của group theo đúng cơ chế Laravel
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

	/**
	 * Nếu gọi method thông thường (non-static) → chuyển sang static handler
	 */
	public function __call($name, $arguments) {
		return static::__callStatic($name, $arguments);
	}

	/**
	 * Xử lý tất cả method động theo kiểu Laravel
	 */
	public static function __callStatic($name, $arguments) {
		$lower = strtolower($name);

		/**
		 * 1) Nếu gọi prefix(), name(), middleware()
		 * → chỉ lưu vào pending, chưa tạo route
		 */
		if (in_array($lower, ['prefix', 'name', 'middleware'])) {

			// middleware có thể là array hoặc string
			if ($lower === 'middleware') {
				self::$pending['middlewares'] = is_array($arguments[0])
					? $arguments[0]
					: [$arguments[0]];
			}
			else {
				// prefix hoặc name
				self::$pending[$lower] = $arguments[0];
			}

			// trả về new static để chain tiếp
			return new static;
		}

		/**
		 * 2) Xử lý group()
		 * → tạo phạm vi group và áp dụng prefix/name/middleware cho các route con
		 */
		elseif ($lower === 'group') {

			// Lấy toàn bộ giá trị pending trước group
			$attrs = [
				'prefix'      => self::$pending['prefix'] ?? '',
				'name'        => self::$pending['name'] ?? '',
				'middlewares' => self::$pending['middlewares'] ?? [],
			];

			// Nếu group có khai báo name() → push vào nameStack giống Laravel
			if (!empty($attrs['name'])) {
				self::$nameStack[] = $attrs['name'];
			}

			// Push toàn bộ thuộc tính group vào RouteManager
			RouteManager::pushGroupAttributes($attrs);

			// reset pending để không ảnh hưởng route khác
			self::$pending = [];

			// chạy callback group (tạo route con)
			$callback = $arguments[0];
			$callback();

			// Sau khi group kết thúc → remove prefix name
			if (!empty($attrs['name'])) {
				array_pop(self::$nameStack);
			}

			// pop group attributes khỏi stack
			RouteManager::popGroupAttributes();

			return new static;
		}

		/**
		 * 3) Xử lý HTTP verbs (get/post/put/patch/delete/options)
		 * Đây là lúc route thực sự được tạo.
		 */
//		if (in_array($lower, ['get', 'post', 'put', 'patch', 'delete', 'options'])) {
		else {
			$method   = $lower;
			$path     = $arguments[0];
			$callback = $arguments[1] ?? null;

			// Lấy attributes của tất cả group đang active
			$group = RouteManager::currentGroupAttributes();

			/**
			 * Hợp nhất prefix tạm (chỉ có tác dụng cho route này)
			 * Ví dụ:
			 *     Route::prefix('x')->get(...)
			 */
			if (!empty(self::$pending['prefix'])) {
				$group['prefix'] .= rtrim(self::$pending['prefix'], '/') . '/';
			}

			/**
			 * Hợp nhất name tạm
			 * Ví dụ:
			 *     Route::name('x.')->get(...)
			 */
			if (!empty(self::$pending['name'])) {
				$group['name'] .= self::$pending['name'];
			}

			/**
			 * Hợp nhất middleware tạm
			 */
			if (!empty(self::$pending['middlewares'])) {
				$group['middlewares'] = array_values(array_unique(array_merge(
					$group['middlewares'],
					self::$pending['middlewares']
				)));
			}

			/**
			 * 4) Tạo đối tượng RouteData
			 * RouteData sẽ giữ method, uri, callback, prefix, middlewares
			 */
			$type = basename(str_replace('\\', '/', static::class));
			$route = new RouteData($type, $method, $path, $callback, $group);

			/**
			 * Gắn nameStack hiện tại vào route
			 * Khi người dùng gọi ->name('abc') thì RouteData sẽ dùng nameStack
			 * để build full route name giống Laravel.
			 */
			$route->setGroupNameStack(self::$nameStack);

			// Lưu route vào RouteManager
			RouteManager::addRoute($route);

			// reset pending sau khi tạo route
			self::$pending = [];

			return $route;
		}

		return null;
	}

}
