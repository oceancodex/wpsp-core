<?php

namespace WPSPCORE\Routes;

/**
 * @method $this middleware(array|string $middlewares)
 * @method $this group($callback)
 * @method $this name(string $name)
 * @method $this prefix(string $prefix)
 *
 * @method $this get(string $action, $callback)
 * @method $this post(string $action, $callback)
 */
class AjaxsRoute {

	protected static array $pending = [];

	/**
	 * Stack lưu prefix name giống Laravel (không dùng global)
	 */
	protected static array $nameStack = [];

	public function __call($name, $arguments) {
		return static::__callStatic($name, $arguments);
	}

	public static function __callStatic($name, $arguments) {
		$lower = strtolower($name);

		// prefix, name, middleware -> lưu pending
		if (in_array($lower, ['prefix', 'name', 'middleware'])) {

			if ($lower === 'middleware') {
				self::$pending['middlewares'] = is_array($arguments[0])
					? $arguments[0]
					: [$arguments[0]];
			}
			else {
				self::$pending[$lower] = $arguments[0];
			}

			return new static;
		}

		// group()
		if ($lower === 'group') {

			$attrs = [
				'prefix'      => self::$pending['prefix'] ?? '',
				'name'        => self::$pending['name'] ?? '',
				'middlewares' => self::$pending['middlewares'] ?? [],
			];

			// 1) Push name prefix vào stack
			if (!empty($attrs['name'])) {
				self::$nameStack[] = $attrs['name'];
			}

			// 2) Push toàn bộ group attributes vào RouteManager stack
			RouteManager::pushGroupAttributes($attrs);

			// clear pending
			self::$pending = [];

			// run child routes
			$callback = $arguments[0];
			$callback();

			// POP name prefix
			if (!empty($attrs['name'])) {
				array_pop(self::$nameStack);
			}

			RouteManager::popGroupAttributes();

			return new static;
		}

		// http verbs
		if (in_array($lower, ['get', 'post', 'put', 'patch', 'delete', 'options'])) {

			$method   = strtoupper($lower);
			$uri      = $arguments[0];
			$callback = $arguments[1] ?? null;

			$group = RouteManager::currentGroupAttributes();

			// merge pending prefix
			if (!empty(self::$pending['prefix'])) {
				$group['prefix'] .= rtrim(self::$pending['prefix'], '/') . '/';
			}

			// merge pending name
			if (!empty(self::$pending['name'])) {
				$group['name'] .= self::$pending['name'];
			}

			// merge pending middleware
			if (!empty(self::$pending['middlewares'])) {
				$group['middlewares'] = array_values(array_unique(array_merge(
					$group['middlewares'],
					self::$pending['middlewares']
				)));
			}

			// CREATE ROUTE
			$route = new RouteData($method, $uri, $callback, $group);

			// GÁN stack name để RouteData xử lý
			$route->setGroupNameStack(self::$nameStack);

			RouteManager::addRoute($route);

			self::$pending = [];

			return $route;
		}

		return null;
	}

}
