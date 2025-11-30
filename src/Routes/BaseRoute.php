<?php

namespace WPSPCORE\Routes;

use WPSPCORE\BaseInstances;
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

	use RouteTrait, HookRunnerTrait;

	/**
	 * Instance ví dụ: \WPSP\App\Instances\Routes\Apis
	 */
	public static $instance = null;

	/**
	 * Lưu các giá trị được gọi trước khi gọi HTTP verb\
	 * Ví dụ:
	 *     Route::prefix('abc')->middleware(XYZ::class)->get(...)
	 *
	 * Các giá trị prefix: name, middlewares, namespace, version,... sẽ được lưu vào đây trước.
	 */
	protected array $pending = [];

	/**
	 * Stack dùng để lưu các prefix name của group.\
	 * Ví dụ:\
	 *     Route::name('admin.')->group(function() {\
	 *         ....Route::name('user.')->group(function() {\
	 *             ........Route::get('list')->name('index');\
	 *         ....});\
	 *     });
	 *
	 * nameStack khi chạy route "list" sẽ là:
	 *     ['admin.', 'user.']
	 */
	protected array $nameStack = [];

	/*
	 *
	 */

	public static function instance() {
		return static::$instance;
	}

	/*
	 *
	 */

	/**
	 * Xử lý tất cả method động.
	 */
	public function __call($method, $arguments) {
		$method = strtolower($method);

		/**
		 * 1) Nếu gọi prefix(), name(), middleware()
		 * → chỉ lưu vào pending, chưa tạo route
		 */
		if (in_array($method, ['prefix', 'name', 'middleware', 'namespace', 'version'])) {

			// Xử lý middlewares.
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

				/**
				 * Áp dụng chỗ này để tạo ra mảng middleware blocks.\
				 * Group cha có middlewares: a, b, c\
				 * Group con nằm trong group cha có middlewares: d, e, f\
				 * Route nằm trong group con.
				 * Thì Route sẽ trải qua block middleware: a, b, c trước rồi mới đến block middleware: d, e, f.\
				 * Như vậy việc thực thi middlewares cho route sẽ lần lượt từ trên xuống dưới trong group lồng nhau.
				 */
				$this->pending['middlewares'][] = $final;

				return $this;
			}
			else {
				$this->pending[$method] = $arguments[0];
			}

			return $this;
		}

		/**
		 * 2) Xử lý group()
		 * → tạo phạm vi group và áp dụng prefix/name/middleware cho các route con
		 */
		elseif ($method === 'group') {

			// Lấy toàn bộ giá trị pending trước group
			$attrs = [
				'prefix'      => $this->pending['prefix'] ?? null,
				'name'        => $this->pending['name'] ?? null,
				'middlewares' => $this->pending['middlewares'] ?? [],
				'namespace'   => $this->pending['namespace'] ?? null,
				'version'     => $this->pending['version'] ?? null,
			];

			// Nếu group có khai báo name() → push vào nameStack.
			if (!empty($attrs['name'])) {
				$this->nameStack[] = $attrs['name'];
			}

			// Push toàn bộ thuộc tính group vào RouteManager
			$this->funcs->getRouteManager()->pushGroupAttributes($attrs);

			// reset pending để không ảnh hưởng route khác
			$this->pending = [];

			// chạy callback group (tạo route con)
			$callback = $arguments[0];
			$callback();

			// Sau khi group kết thúc → remove prefix name
			if (!empty($attrs['name'])) {
				array_pop($this->nameStack);
			}

			// pop group attributes khỏi stack
			$this->funcs->getRouteManager()->popGroupAttributes();

			return $this;
		}

		/**
		 * 3) Xử lý HTTP verbs (get/post/put/patch/delete/options)
		 * Đây là lúc route thực sự được tạo.
		 */
		return $this->buildRoute($method, $arguments);
	}

	/**
	 * Nếu gọi static method, chuyển sang method thông thường với instance.\
	 * Instance ví dụ: \WPSP\App\Instances\Routes\Apis
	 */
	public static function __callStatic($method, $arguments) {
		return static::instance()->__call($method, $arguments);
	}

	/*
	 *
	 */

	/**
	 * Tạo đối tượng RouteData và lưu vào RouteManager.
	 */
	public function buildRoute($method, $arguments): RouteData {
		$path     = $arguments[0];
		$callback = $arguments[1] ?? null;
		$args     = $arguments[2] ?? [];

		// Lấy attributes của tất cả group đang active
		$group = $this->funcs->getRouteManager()->currentGroupAttributes();

		/**
		 * Hợp nhất prefix tạm (chỉ có tác dụng cho route này)
		 * Ví dụ:
		 *     Route::prefix('x')->get(...)
		 */
		if (!empty($this->pending['prefix'])) {
			$group['prefix'] .= rtrim($this->pending['prefix'], '/') . '/';
		}

		/**
		 * Hợp nhất name tạm
		 * Ví dụ:
		 *     Route::name('x.')->get(...)
		 */
		if (!empty($this->pending['name'])) {
			$group['name'] .= $this->pending['name'];
		}

		/**
		 * Hợp nhất middleware tạm
		 */
		// Hợp nhất middleware tạm (an toàn nếu key không tồn tại)
		$groupMiddlewares   = $group['middlewares'] ?? [];
		$pendingMiddlewares = $this->pending['middlewares'] ?? [];

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
		if (array_key_exists('namespace', $this->pending)) {
			$group['namespace'] = $this->pending['namespace'];
		}

		/**
		 * Hợp nhất version tạm (override)
		 */
		if (array_key_exists('version', $this->pending)) {
			$group['version'] = $this->pending['version'];
		}

		/**
		 * 4) Tạo đối tượng RouteData
		 * RouteData sẽ giữ method, path, callback, prefix, middlewares
		 */
		$routeClass = get_class($this);
		$type       = basename(str_replace('\\', '/', $routeClass));
		$route      = new RouteData(
			$type,
			$routeClass,
			$method,
			$path,
			$callback,
			$args,
			$group,
			$this->funcs
		);

		/**
		 * Gắn nameStack hiện tại vào route
		 * Khi người dùng gọi ->name('abc') thì RouteData sẽ dùng nameStack để build full route name.
		 */
		$route->setGroupNameStack($this->nameStack);

		// Nếu pending có name → áp dụng cho route
		if (!empty($this->pending['name'])) {
			$route->name($this->pending['name']);
		}

		// Lưu route vào RouteManager.
		$this->funcs->getRouteManager()->addRoute($route);

		// Reset pending sau khi tạo route.
		$this->pending = [];

		return $route;
	}

}
