<?php

namespace WPSPCORE\Routes;

use WPSPCORE\Base\BaseInstances;
use WPSPCORE\Traits\HookRunnerTrait;

/**
 * Hỗ trợ gọi động: prefix(), name(), middleware(), group(),
 *
 * @method $this name(string $name)
 * @method $this group($callback)
 * @method $this prefix(string $prefix)
 * @method $this namespace(string $namespace)
 * @method $this version(string $version)
 * @method $this middleware(array|string $middlewares)
 */
abstract class BaseRoute extends BaseInstances {

	use BaseRouteTrait, HookRunnerTrait;

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
		if (in_array($method, ['prefix', 'name', 'middleware', 'namespace', 'version'])) {

			// middleware dạng array hoặc string
//			if ($method === 'middleware') {
//				static::$pending['middlewares'] = is_array($arguments[0])
//					? $arguments[0]
//					: [$arguments[0]];
//			}
			if ($method === 'middleware') {
				$raw = $arguments;

				// Hợp nhất tham số (để hỗ trợ cả dạng middleware(a,b,c))
				if (count($raw) === 1 && is_array($raw[0])) {
					$raw = $raw[0];
				}

				$middlewares = [];
				$relation = null;

				foreach ($raw as $key => $item) {

					// Nếu là relation
					if ($key === 'relation' || $item === 'relation' || $key === 0 && is_string($item) && str_starts_with($item, 'relation')) {
						$relation = is_array($raw) && isset($raw['relation'])
							? $raw['relation']
							: (is_string($item) ? $item : null);
						continue;
					}

					// Nếu là class string: Namespace\Class
					if (is_string($item)) {
						$middlewares[] = [$item, 'handle'];
						continue;
					}

					// Nếu là [class, method]
					if (is_array($item)) {
						// item[0] = class
						// item[1] = method (optional)
						$class = $item[0] ?? null;
						$method = $item[1] ?? 'handle';

						if ($class) {
							$middlewares[] = [$class, $method];
						}
						continue;
					}

					// Nếu dạng không hợp lệ → bỏ qua
				}

				// Ghép lại đầy đủ format mong muốn
				$final = [];

				if ($relation !== null) {
					$final['relation'] = $relation;
				}

				foreach ($middlewares as $mw) {
					$final[] = $mw;
				}

				static::$pending['middlewares'] = $final;

				return new static();
			}
			else {
				static::$pending[$method] = $arguments[0];
			}

			return new static();
		}

		/**
		 * 2) Xử lý group()
		 * → tạo phạm vi group và áp dụng prefix/name/middleware cho các route con
		 */
		elseif ($method === 'group') {

			// Lấy toàn bộ giá trị pending trước group
			$attrs = [
				'prefix'      => static::$pending['prefix'] ?? null,
				'name'        => static::$pending['name'] ?? null,
				'middlewares' => static::$pending['middlewares'] ?? [],
				'namespace'   => static::$pending['namespace'] ?? null,
				'version'     => static::$pending['version'] ?? null,
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
		// Hợp nhất middleware tạm (an toàn nếu key không tồn tại)
		$groupMiddlewares   = $group['middlewares'] ?? [];
		$pendingMiddlewares = static::$pending['middlewares'] ?? [];

		if (!empty($pendingMiddlewares)) {

			// Merge thẳng
			$merged = array_merge($groupMiddlewares, $pendingMiddlewares);

			// Unique an toàn cho phần tử có thể là array hoặc string
			$unique = [];
			$seen   = [];

			foreach ($merged as $mw) {
				// String -> dùng chính string làm key
				if (is_string($mw)) {
					$hash = 'str:' . $mw;
					if (!isset($seen[$hash])) {
						$seen[$hash] = true;
						$unique[] = $mw;
					}
					continue;
				}

				// Array -> serialize để tạo key (an toàn cho nested arrays)
				if (is_array($mw)) {
					$hash = 'arr:' . serialize($mw);
					if (!isset($seen[$hash])) {
						$seen[$hash] = true;
						$unique[] = $mw;
					}
					continue;
				}

				// Khác (object/number...) -> cast sang string làm fallback key
				$hash = 'oth:' . @serialize($mw);
				if (!isset($seen[$hash])) {
					$seen[$hash] = true;
					$unique[] = $mw;
				}
			}

			$group['middlewares'] = array_values($unique);
		} else {
			// Nếu pending rỗng thì giữ nguyên group middlewares (hoặc đảm bảo key tồn tại)
			$group['middlewares'] = $groupMiddlewares;
		}

		/**
		 * Hợp nhất namespace tạm (override)
		 */
		if (array_key_exists('namespace', static::$pending)) {
			$group['namespace'] = static::$pending['namespace'];
		}

		/**
		 * Hợp nhất version tạm (override)
		 */
		if (array_key_exists('version', static::$pending)) {
			$group['version'] = static::$pending['version'];
		}


		/**
		 * 4) Tạo đối tượng RouteData
		 * RouteData sẽ giữ method, path, callback, prefix, middlewares
		 */
		$routeClass = static::class;
		$type       = basename(str_replace('\\', '/', $routeClass));
		$route      = new RouteData(
			$type,
			$routeClass,
			$method,
			$path,
			$callback,
			$args,
			$group,
			static::$funcs
		);

		/**
		 * Gắn nameStack hiện tại vào route
		 * Khi người dùng gọi ->name('abc') thì RouteData sẽ dùng nameStack để build full route name.
		 */
		$route->setGroupNameStack(static::$nameStack);

		// Nếu pending có name → áp dụng cho route
		if (!empty(static::$pending['name'])) {
			$route->name(static::$pending['name']);
		}

		// Lưu route vào RouteManager.
		static::$funcs->getRouteManager()::addRoute($route);

		// Reset pending sau khi tạo route.
		static::$pending = [];

		return $route;
	}

}
