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

	public function beforeConstruct(): void {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route): void {
		$fullAction = $route->fullPath;
		$nopriv = $route->args['nopriv'] ?? false;

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
		$action        = $route->path;
		$fullAction    = $route->fullPath;
		$requestPath = trim($this->request->getRequestUri(), '/\\');

		/**
		 * Đăng ký ajax action ngay mà không cần kiểm tra phương thức HTTP.
		 *  add_action chỉ đăng ký callback khi load plugin/theme.
		 *  Kiểm tra request method phải thực hiện bên trong callback xử lý AJAX.
		 */
		add_action($hookAction, function() use ($hookAction, $action, $fullAction, $requestPath, $route) {
			$callback    = $route->callback;
			$middlewares = $route->middlewares;
			if (!$this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])) {
				wp_send_json($this->funcs->_response(false, null, 'Access denied.'), 403);
				return;
			}

			$constructParams = [
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
				[
					'action'            => $action,
					'full_action'       => $fullAction,
					'callback_function' => $callback[1] ?? null,
				],
			];

			$callback   = $this->prepareRouteCallback($callback, $constructParams);
			$callParams = $this->getCallParams($action, $fullAction, $requestPath, $callback[0], $callback[1]);
			$this->resolveAndCall($callback, $callParams);
		});

	}

}