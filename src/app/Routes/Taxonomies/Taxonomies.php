<?php

namespace WPSPCORE\App\Routes\Taxonomies;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this taxonomy(string $taxonomy, callable|array $callback, array $args = [])
 */
class Taxonomies extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$taxonomy    = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;
		if ($this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])) {
			$constructParams = [
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
				[
					'taxonomy'          => $taxonomy,
					'callback_function' => $callback[1] ?? null,
				],
			];

			$callback    = $this->prepareRouteCallback($callback, $constructParams);
			$callback[1] = 'init';
			$callParams  = $this->getCallParams($taxonomy, $taxonomy, $route->fullPath, $callback[0], $callback[1]);
			$this->resolveAndCall($callback, $callParams);
		}
	}

}