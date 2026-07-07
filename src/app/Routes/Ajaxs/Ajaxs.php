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
		$requestPath = ltrim($this->request->getRequestUri(), '/\\');

		$path          = $route->path;
		$pathRegex     = $route->pathRegex;
		$fullPath      = $route->fullPath;
		$fullPathRegex = $route->fullPathRegex;
		$callback      = $route->callback;
		$middlewares   = $route->middlewares;

		add_action($hookAction, function() use ($hookAction, $route, $requestPath, $path, $pathRegex, $fullPath, $fullPathRegex, $callback, $middlewares) {
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
					'path_regex'        => $pathRegex,
					'full_path'         => $fullPath,
					'full_path_regex'   => $fullPathRegex,
					'callback_function' => $callback[1] ?? null,
				],
			];

			/**
			 * Vì thế, DI tại đây được triển khai với method "init".\
			 * Thành ra method "index" khi gọi trong "init" sẽ không có DI.\
			 * Cần phải truyền thêm "route" vào "extraParams" trong "constructParams"\
			 * để DI hoạt động được với method "index".
			 */
			$constructParams[3]['route'] = $route;

			/**
			 * Hợp nhất contructParams[3] (gọi là extraParams) với args được truyền từ route vào nhau.\
			 * Mục đích để callback Class có thể sử dụng được.
			 */
			$constructParams[3] = array_merge($constructParams[3], $route->args);

			/**
			 * Thực hiện các công việc với Callback.
			 * 1. Chuẩn bị callback.
			 * 2. Chuẩn bị parameters mà callback sử dụng.
			 * 3. Xử lý callback với parameters (DI).
			 * 4. Gọi callback.
			 */
			$callback   = $this->prepareRouteCallback($callback, $constructParams);
			$callParams = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1], ['route' => $route]);
			$this->resolveAndCall($callback, $callParams);
		});

	}

}