<?php

namespace WPSPCORE\App\Routes\NavigationMenus\Locations;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this nav_location(string $location, callable|array $callback, array $args = [])
 */
class Locations extends BaseRoute {

	public function beforeConstruct(): void {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route): void {
		$location = $route->fullPath;
		$callback = $route->callback;

		$constructParams = [
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv(),
			[
				'location'          => $location,
				'callback_function' => $callback[1] ?? null,
			],
		];

		$callback    = $this->prepareRouteCallback($callback, $constructParams);
		$callback[1] = 'init';
		$callParams  = $this->getCallParams($location, $location, $route->fullPath, $callback[0], $callback[1]);
		$this->resolveAndCall($callback, $callParams);
	}

}