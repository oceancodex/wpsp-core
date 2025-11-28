<?php

namespace WPSPCORE\Routes\Ajaxs;

use WPSPCORE\Routes\BaseRoute;

/**
 * @method static $this get(string $path, callable|array $callback)
 * @method static $this post(string $path, callable|array $callback)
 * @method static $this put(string $path, callable|array $callback)
 * @method static $this patch(string $path, callable|array $callback)
 * @method static $this delete(string $path, callable|array $callback)
 * @method static $this options(string $path, callable|array $callback)
 * @method static $this head(string $path, callable|array $callback)
 */
class Ajaxs extends BaseRoute {

	public function beforeConstruct(): void {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route): void {
		$this->executeMethod($route);
	}

	/*
	 *
	 */

	public function executeMethod($route): void {
	}

	/*
	 *
	 */

	public function addAjaxAction($route): void {
		add_action($action, function() use ($route) {
			if (!$this->isPassedMiddleware($allMiddlewares, $this->request, [
				'action' => $action,
				'path' => $path,
				'full_path' => $fullPath,
				'middlewares' => $allMiddlewares,
				'custom_properties' => $customProperties,
			])) {
				wp_send_json($this->funcs->_response(false, null, 'Access denied.'), 403);
				return;
			}

			$constructParams = [
				[
					'action'            => $action,
					'path'              => $path,
					'full_path'         => $fullPath,
					'callback_function' => $callback[1] ?? null,
					'custom_properties' => $customProperties,
				],
			];
			$constructParams = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
			], $constructParams);
			$callback = $this->prepareRouteCallback($callback, $constructParams);

			$requestPath = trim($this->request->getRequestUri(), '/\\');
			$callParams = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1]);
			$this->resolveAndCall($callback, $callParams);
		});
	}

}