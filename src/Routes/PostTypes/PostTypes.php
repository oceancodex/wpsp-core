<?php

namespace WPSPCORE\Routes\PostTypes;

use WPSPCORE\Routes\BaseRoute;

/**
 * @method static $this post_type(string $postType, callable|array $callback, array $args = [])
 */
class PostTypes extends BaseRoute {

	public function beforeConstruct(): void {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route): void {
		$requestPath = trim($this->request->getRequestUri(), '/\\');

		$postType    = $route->path;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;

		if ($this->isPassedMiddleware($middlewares, $this->request, [
			'post_type'   => $postType,
			'middlewares' => $middlewares,
		])) {
			if (is_array($callback) || is_callable($callback) || is_null($callback[1])) {
				$constructParams = [
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
					[
						'post_type'         => $postType,
						'callback_function' => $callback[1] ?? null,
					],
				];

				$callback    = $this->prepareRouteCallback($callback, $constructParams);
				$callback[1] = 'init';
				$callParams  = $this->getCallParams($postType, $postType, $requestPath, $callback[0], $callback[1]);
				$this->resolveAndCall($callback, $callParams);
			}
		}
	}

}