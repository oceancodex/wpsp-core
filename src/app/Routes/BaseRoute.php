<?php

namespace WPSPCORE\App\Routes;

use WPSPCORE\App\Traits\HookRunnerTrait;
use WPSPCORE\BaseInstances;

/**
 * Hỗ trợ gọi động: prefix(), name(), middleware(), group(),
 *
 * @method static $this name(string $name)
 * @method static $this group($callback)
 * @method static $this prefix(string $prefix)
 * @method static $this namespace(string $namespace)
 * @method static $this version(string $version)
 * @method static $this middleware(array|string ...$middlewares)
 */
abstract class BaseRoute extends BaseInstances {

	use HookRunnerTrait;

	/**
	 * Namespace và Version mặc định cho tất cả các route (ví dụ: WPSP, v1).\
	 * Nếu route là "Apis" thì "defaultNamespace" và "defaultVersion" sẽ được\
	 * định nghĩa trong class "as Route" ở thư mục "Widen".
	 */
	public $defaultNamespace = null;
	public $defaultVersion   = null;

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
	protected $pending = [];

	/**
	 * Stack dùng để lưu các name của group lồng nhau.\
	 * Ví dụ:\
	 * ㅤRoute::name('admin.')->group(function() {\
	 * ㅤㅤRoute::name('user.')->group(function() {\
	 * ㅤㅤㅤRoute::get('list')->name('index');\
	 * ㅤㅤ});\
	 * ㅤ});
	 *
	 * nameStack khi chạy route "list" sẽ là:
	 *     ['admin.', 'user.']
	 */
	protected $nameStack = [];

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

				/**
				 * Build block middleware, hỗ trợ đệ quy các block con lồng nhau. Ví dụ:
				 *
				 * Route::middleware([
				 *     'relation' => 'OR',
				 *     ['relation' => 'OR', 'throttle:3rpm', EditorCapability::class],
				 *     ['relation' => 'AND', AdministratorCapability::class, TestMiddleware::class],
				 * ])->get(...)
				 */
				$final = $this->buildMiddlewareBlock($raw);

				$this->pending['middlewares'][][] = $final;

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
			$this->funcs->_getRouteManager()?->pushGroupAttributes($attrs);

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
			$this->funcs->_getRouteManager()?->popGroupAttributes();

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
	 * Chuẩn hoá 1 phần tử middleware (lá hoặc block lồng nhau).\
	 * - Closure                          → giữ nguyên
	 * - string ("Class::class"/"throttle:..") → [string, 'handle']
	 * - [class, method]                  → bổ sung 'handle' nếu thiếu method
	 * - block lồng nhau (có key 'relation') → đệ quy build lại thành block con
	 */
	private function normalizeMiddlewareItem($item) {
		if ($item instanceof \Closure) {
			return $item;
		}

		if (is_string($item)) {
			return [$item, 'handle'];
		}

		if (is_array($item)) {
			// Block lồng nhau: có key 'relation' → đệ quy.
			if (array_key_exists('relation', $item)) {
				return $this->buildMiddlewareBlock($item);
			}

			// Dạng [class, method]
			$class      = $item[0] ?? null;
			$itemMethod = $item[1] ?? 'handle';

			if ($class) {
				return [$class, $itemMethod];
			}
		}

		return $item;
	}

	/**
	 * Build 1 block middleware, hỗ trợ các block con lồng nhau bên trong.
	 */
	private function buildMiddlewareBlock($raw) {
		$relation = null;

		if (array_key_exists('relation', $raw)) {
			$relation = $raw['relation'];
			unset($raw['relation']);
		}

		$final = [];
		if ($relation !== null) {
			$final['relation'] = $relation;
		}

		foreach ($raw as $item) {
			$final[] = $this->normalizeMiddlewareItem($item);
		}

		return $final;
	}

	/**
	 * Tạo đối tượng RouteData và lưu vào RouteManager.
	 */
	public function buildRoute($method, $arguments): RouteData {
		$path     = $arguments[0];
		$callback = $arguments[1] ?? null;
		$args     = $arguments[2] ?? []; // "args" là tham số thứ 3 trong Route. Ví dụ: Route::get(name, callback, args)

		/**
		 * Lấy ra class "as Route" trong "Widen".\
		 * Sau đó khai báo $type là tên của class đó.
		 */
		$routeClass = get_class($this);
		$type       = basename(str_replace('\\', '/', $routeClass));

		/**
		 * Lấy attributes của tất cả group đang active.\
		 * Truyền $type vào để thực hiện một số công việc cụ thể.
		 */
		$group = $this->funcs->_getRouteManager()?->currentGroupAttributes($type);

		/**
		 * Hợp nhất prefix tạm (chỉ có tác dụng cho route này)
		 * Ví dụ:
		 *     Route::prefix('x')->get(...)
		 */
		if (!empty($this->pending['prefix'])) {
			$group['prefix'] .= rtrim($this->pending['prefix'], '/').'/';
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
					$hash = 'str:'.$mw;
					if (!isset($seen[$hash])) {
						$seen[$hash] = true;
						$unique[]    = $mw;
					}
					continue;
				}

				// Array -> serialize để tạo key (an toàn cho nested arrays)
				if (is_array($mw)) {
					$hash = 'arr:'.serialize($mw);
					if (!isset($seen[$hash])) {
						$seen[$hash] = true;
						$unique[]    = $mw;
					}
					continue;
				}

				// Khác (object/number...) -> cast sang string làm fallback key
				$hash = 'oth:'.@serialize($mw);
				if (!isset($seen[$hash])) {
					$seen[$hash] = true;
					$unique[]    = $mw;
				}
			}

			$group['middlewares'] = array_values($unique);
		}
		else {
			// Nếu pending rỗng thì giữ nguyên group middlewares (hoặc đảm bảo key tồn tại)
			$group['middlewares'] = $groupMiddlewares;
		}

		/**
		 * Hợp nhất namespace tạm (override)
		 */
		if (array_key_exists('namespace', $this->pending)) {
			$group['namespace'] = $this->pending['namespace'];
		}
		elseif (!empty($group['namespace'])) {
			$group['namespace'] = $group['namespace'].'';
		}
		else {
			$group['namespace'] = $this->defaultNamespace ?? $this->funcs->_getRootNamespace() ?? null;
		}

		/**
		 * Hợp nhất version tạm (override)
		 */
		if (array_key_exists('version', $this->pending)) {
			$group['version'] = $this->pending['version'];
		}
		elseif (!empty($group['version'])) {
			$group['version'] = $group['version'].'';
		}
		else {
			$group['version'] = $this->defaultVersion ?? null;
		}

		/**
		 * Tạo đối tượng RouteData\
		 * RouteData sẽ giữ method, path, callback, prefix, middlewares
		 */
		$route = new RouteData(
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
		$this->funcs->_getRouteManager()?->addRoute($route);

		// Reset pending sau khi tạo route.
		$this->pending = [];

		return $route;
	}

}
