<?php

namespace WPSPCORE\Routes\AdminPages;

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
class AdminPages extends BaseRoute {

	public function beforeConstruct(): void {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route): void {
		$request  = $this->request;
		$method   = $route->method;
		$callback = $route->callback;

		if (!empty($callback) && is_admin() && !wp_doing_ajax() && !wp_doing_cron() && !$this->funcs->_wantsJson()) {
			if (strtolower($method) == 'get') {
				$this->executeMethodGet($route);
			}
			else {
				if ($request->getMethod() !== 'GET' && $request->isMethod(strtoupper($method))) {
					$this->executeMethod($route);
				}
			}
		}
	}

	/*
	 *
	 */

	public function executeMethod($route): void {
		$request = $this->request;

		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;


		$screenOptions = $request->get('wp_screen_options');
		if ($screenOptions) {
			return;
		}

		$requestPath = trim($request->getRequestUri(), '/\\');
		if (
			(
				($callback instanceof \Closure)
				||
				(
					(is_array($callback) || is_callable($callback))
					&&
					(isset($callback[1]) && $callback[1] !== 'index')
				)
			)
			&&
			(
				($request->get('page') == $fullPath && preg_match('/' . $this->funcs->_regexPath($fullPath) . '$/iu', $requestPath))
				|| preg_match('/' . $this->funcs->_regexPath($fullPath) . '$/iu', $requestPath)
			)
		) {
			if ($this->isPassedMiddleware($middlewares, $request, ['route' => $route])) {
				$constructParams = [
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
					[
						'path'              => $path,
						'full_path'         => $fullPath,
						'callback_function' => $callback[1],
					],
				];
				$callback        = $this->prepareRouteCallback($callback, $constructParams);
				$callParams      = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1]);
				$this->resolveAndCall($callback, $callParams);
//				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}(...$callParams) : $callback;
			}
			else {
				wp_die(
					'<h1>ERROR: 403 - Truy cập bị từ chối</h1>' .
					'<p>Bạn không được phép truy cập vào trang này.</p>',
					'ERROR: 403 - Truy cập bị từ chối',
					[
						'response'  => 403,
						'back_link' => true,
					]
				);
			}
		}
	}

	public function executeMethodGet($route): void {
		$request     = $this->request;
		$requestPath = trim($request->getRequestUri(), '/\\');

		$method      = $route->method;
		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;

		if (
			($callback instanceof \Closure)
			|| (
				(is_array($callback) || is_callable($callback) || is_null($callback[1]))
				&& (
					!isset($callback[1])
					|| $callback[1] == 'index'
					|| $request->get('page') == $fullPath
					|| preg_match('/' . $this->funcs->_regexPath($fullPath) . '$/iu', $requestPath)
				)
			)
		) {
			if ($this->isPassedMiddleware($middlewares, $request, ['route' => $route])) {
				$constructParams = [
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
					[
						'path'              => $path,
						'full_path'         => $fullPath,
						'callback_function' => $callback instanceof \Closure ? $callback : $callback[1] ?? null,
					],
				];

				if ($callback instanceof \Closure) {
					add_action('admin_menu', function() use ($fullPath, $callback) {
						if (is_array($callback)) {
							$callbackRef = new \ReflectionMethod($callback[0], $callback[1]);
						}
						else {
							$callbackRef = new \ReflectionFunction($callback);
						}
						$params = $callbackRef->getParameters();
						$args   = [];
						foreach ($params as $param) {
							$name = $param->getName();

							if ($param->isDefaultValueAvailable()) {
								$default = $param->getDefaultValue();
							}
							else {
								$default = null;
							}
							$args[$name] = $default;
						}
						if (isset($args['is_submenu_page']) && $args['is_submenu_page']) {
							add_submenu_page(
								$args['parent_slug'] ?? 'options-general.php',
								$args['page_title'] ?? $fullPath,
								$args['menu_title'] ?? $fullPath,
								$args['capability'] ?? 'manage_options',
								$args['menu_slug'] ?? $fullPath,
								$callback,
								$args['position'] ?? null
							);
						}
						else {
							add_menu_page(
								$args['page_title'] ?? $fullPath,
								$args['menu_title'] ?? $fullPath,
								$args['capability'] ?? 'manage_options',
								$args['menu_slug'] ?? $fullPath,
								$callback,
								$args['icon_url'] ?? null,
							);
						}
					});
				}
				else {
					if (isset($callback[1]) && is_string($callback[1]) && $callback[1] !== 'index') {
						if (preg_match('/' . $this->funcs->_regexPath($fullPath) . '$/iu', $requestPath)) {
							$callback   = $this->prepareRouteCallback($callback, $constructParams);
							$callParams = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1]);
							$this->resolveAndCall($callback, $callParams);
						}
					}
					else {
						if (($callback[1] == 'index' || !isset($callback[1]))) $callback[1] = 'init';
						$callback   = $this->prepareRouteCallback($callback, $constructParams);
						$callParams = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1]);
						$this->resolveAndCall($callback, $callParams);
					}
				}
			}
			elseif (preg_match('/' . $this->funcs->_regexPath($fullPath) . '$/iu', $requestPath)) {
				wp_die(
					'<h1>ERROR: 403 - Truy cập bị từ chối</h1>' .
					'<p>Bạn không được phép truy cập vào trang này.</p>',
					'ERROR: 403 - Truy cập bị từ chối',
					[
						'response'  => 403,
						'back_link' => true,
					]
				);
			}
		}
	}

}