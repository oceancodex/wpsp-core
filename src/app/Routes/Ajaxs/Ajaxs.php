<?php

namespace WPSPCORE\App\Routes\Ajaxs;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this get(string $action, callable|array $callback, array $args = [])
 * @method static $this post(string $action, callable|array $callback, array $args = [])
 * @method static $this put(string $action, callable|array $callback, array $args = [])
 * @method static $this patch(string $action, callable|array $callback, array $args = [])
 * @method static $this delete(string $action, callable|array $callback, array $args = [])
 * @method static $this options(string $action, callable|array $callback, array $args = [])
 * @method static $this head(string $action, callable|array $callback, array $args = [])
 */
class Ajaxs extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$action     = $route->path;
		$fullAction = $route->fullPath;
		$method     = $route->method;
		$nopriv     = $route->args['nopriv'] ?? false;

		/**
		 * Thử nghiệm đăng ký Ajax với việc kiểm tra phương thức HTTP.
		 */
		if ($method !== strtolower($this->request->getMethod())) return;

		$hookAction = 'wp_ajax_' . $fullAction;
		$this->executeMethod($hookAction, $route);
		if ($nopriv) {
			$hookNoprivAction = 'wp_ajax_nopriv_' . $fullAction;
			$this->executeMethod($hookNoprivAction, $route);
		}
	}

	/*
	 *
	 */

	public function executeMethod($hookAction, $route) {
		$requestPath = trim($this->request->getRequestUri(), '/\\');

		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;

		add_action($hookAction, function() use ($hookAction, $route, $requestPath, $path, $fullPath, $callback, $middlewares) {
			if (!$this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])) {
				wp_send_json($this->funcs->_response(false, null, 'Access denied.'), 403);
				return;
			}

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

			$callback   = $this->prepareRouteCallback($callback, $constructParams);
			$callParams = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1]);
			$this->resolveAndCall($callback, $callParams);
		});

	}

}