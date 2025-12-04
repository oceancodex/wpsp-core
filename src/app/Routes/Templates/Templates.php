<?php

namespace WPSPCORE\App\Routes\Templates;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this template(string $name, callable|array $callback, array $args = [])
 */
class Templates extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$name        = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;

		if ($this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])) {
			$constructParams = [
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
				[
					'name'              => $name,
					'callback_function' => $callback[1] ?? null,
				],
			];

			$callback    = $this->prepareRouteCallback($callback, $constructParams);
			$callback[1] = 'init';
			$callParams  = $this->getCallParams($name, $name, $route->fullPath, $callback[0], $callback[1]);
			$this->resolveAndCall($callback, $callParams);
		}
	}

}