<?php

namespace WPSPCORE\App\Routes\WPRoles;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method $this role(string $role, callable|array $callback, array $args = [])
 */
class WPRoles extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$request     = $this->request;
		$requestPath = trim($request->getRequestUri(), '/\\');

		$middlewares = $route->middlewares;
		$path        = $role = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;

		$middlewareArgs    = ['role' => $role];
		$passedMiddlewares = $this->isPassedMiddleware($middlewares, $request, $middlewareArgs);
		if ($passedMiddlewares) {
			$constructParams = [
				$this->mainPath,
				$this->rootNamespace,
				$this->prefixEnv,
				[
					'role'              => $role,
					'callback_function' => $callback[1] ?? null,
				],
			];

			$callback = $this->prepareRouteCallback($callback, $constructParams);
			$callback[1] = 'init';
			$callParams  = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1]);
			$this->resolveAndCall($callback, $callParams);
		}
	}

}