<?php

namespace WPSPCORE\App\Routes\RewriteFrontPages;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this get(string $path, callable|array $callback, array $args = [])
 * @method static $this post(string $path, callable|array $callback, array $args = [])
 * @method static $this put(string $path, callable|array $callback, array $args = [])
 * @method static $this patch(string $path, callable|array $callback, array $args = [])
 * @method static $this delete(string $path, callable|array $callback, array $args = [])
 * @method static $this options(string $path, callable|array $callback, array $args = [])
 * @method static $this head(string $path, callable|array $callback, array $args = [])
 */
class RewriteFrontPages extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$requestPath = trim($this->request->getRequestUri(), '/\\');

		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$method      = $route->method;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;

		if (
			$this->request->method() == strtoupper($method)
			&& preg_match('/' . $this->funcs->_regexPath($fullPath) . '/iu', $requestPath)
			&& $this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])
		) {
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

			$callback    = $this->prepareRouteCallback($callback, $constructParams);
			$callback[1] = 'init';
			$callParams  = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1]);
			$this->resolveAndCall($callback, $callParams);
		}
	}

}