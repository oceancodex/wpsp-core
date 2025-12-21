<?php

namespace WPSPCORE\App\Routes\Filters;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this filter(string $filter, callable|array $callback, array $args = [])
 */
class Filters extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
//		$requestPath = trim($this->request->getRequestUri(), '/\\');

		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;
		$priority    = $route->args['priority'] ?? 10;
		$argsNumber  = $route->args['args_number'] ?? 1;

		if ($this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])) {
			if (is_array($callback) || is_callable($callback) || is_null($callback[1])) {
				$constructParams = [
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
					[
						'path'              => $path,
						'full_path'         => $fullPath,
						'callback_function' => $callback[1] ?? null,
					],
				];

				$callback = $this->prepareRouteCallback($callback, $constructParams);
				add_filter($fullPath, $callback, $priority, $argsNumber);
			}
		}
	}

}