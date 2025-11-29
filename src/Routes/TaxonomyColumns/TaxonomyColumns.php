<?php

namespace WPSPCORE\Routes\TaxonomyColumns;

use WPSPCORE\Routes\BaseRoute;

/**
 * @method static $this column(string $column, callable|array $callback, array $args = [])
 */
class TaxonomyColumns extends BaseRoute {

	public function beforeConstruct(): void {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route): void {
		$column      = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;

		if ($this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])) {
			$constructParams = [
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
				[
					'column'            => $column,
					'callback_function' => $callback[1] ?? null,
				],
			];

			$callback    = $this->prepareRouteCallback($callback, $constructParams);
			$callback[1] = 'init';
			$callParams  = $this->getCallParams($column, $column, $route->fullPath, $callback[0], $callback[1]);
			$this->resolveAndCall($callback, $callParams);
		}
	}

}