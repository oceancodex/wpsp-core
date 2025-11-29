<?php

namespace WPSPCORE\Routes\PostTypeColumns;

use WPSPCORE\Routes\BaseRoute;

/**
 * @method static $this column(string $column, callable|array $callback, array $args = [])
 */
class PostTypeColumns extends BaseRoute {

	public function beforeConstruct(): void {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route): void {
		$requestPath = trim($this->request->getRequestUri(), '/\\');

		$column      = $route->path;
		$fullColumn  = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;

		if ($this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])) {
			if (is_array($callback)) {
				$constructParams = [
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
					[
						'column'            => $column,
						'full_column'       => $fullColumn,
						'callback_function' => $callback[1] ?? null,
					],
				];

				$callback    = $this->prepareRouteCallback($callback, $constructParams);
				$callback[1] = 'init';
				$callParams  = $this->getCallParams($column, $fullColumn, $requestPath, $callback[0], $callback[1]);
				$this->resolveAndCall($callback, $callParams);
			}
		}
	}

}