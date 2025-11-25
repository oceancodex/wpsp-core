<?php

namespace WPSPCORE\Routes;

class RouteManager {

	private static array $routes     = [];
	private static array $groupStack = [];

	public static function pushGroupAttributes(array $attrs) {
		// chuẩn hóa
		$attrs              = [
			'prefix'      => $attrs['prefix'] ?? '',
			'name'        => $attrs['name'] ?? '',
			'middlewares' => $attrs['middlewares'] ?? [],
		];
		self::$groupStack[] = $attrs;
	}

	public static function popGroupAttributes() {
		array_pop(self::$groupStack);
	}

	public static function currentGroupAttributes(): array {
		$merged = ['prefix' => '', 'name' => '', 'middlewares' => []];

		foreach (self::$groupStack as $g) {
			if (!empty($g['prefix'])) {
				// join prefixes with slash but avoid duplicate slashes
				$prefix = rtrim($g['prefix'], '/');
				if ($prefix !== '') {
					$merged['prefix'] = rtrim($merged['prefix'], '/') . '/' . ltrim($prefix, '/');
					$merged['prefix'] = trim($merged['prefix'], '/'); // keep no leading/trailing
					if ($merged['prefix'] !== '') {
						$merged['prefix'] .= '/';
					}
				}
			}

			if (!empty($g['name'])) {
				$merged['name'] .= $g['name'];
			}

			if (!empty($g['middlewares'])) {
				$merged['middlewares'] = array_merge($merged['middlewares'], $g['middlewares']);
			}
		}

		// normalize prefix: ensure either empty or ends with '/'
		if ($merged['prefix'] !== '' && substr($merged['prefix'], -1) !== '/') {
			$merged['prefix'] .= '/';
		}

		return $merged;
	}

	public static function addRoute(RouteData $route) {
		unset($route->nameStack);
		self::$routes[] = $route;
	}

	public static function all(): array {
		return self::$routes;
	}

}
