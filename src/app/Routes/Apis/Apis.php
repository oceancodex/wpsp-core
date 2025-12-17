<?php

namespace WPSPCORE\App\Routes\Apis;

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
class Apis extends BaseRoute {

	public $defaultNamespace = 'wpsp'; // Dòng này không có tác dụng, khai báo cho đẹp thôi, vì nó bị ghi đè trong "beforeConstruct" bên dưới.
	public $defaultVersion   = 'v1';

	/*
	 *
	 */

	public function beforeConstruct() {
		$this->defaultNamespace = $this->funcs->_getAppShortName();
	}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		add_action('rest_api_init', function() use ($route) {
			$this->registerRestRoute($route);
		});
	}

	/*
	 *
	 */

	public function registerRestRoute($route) {
		$requestPath = trim($this->request->getRequestUri(), '/\\');
		$method      = $route->method;
		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares ?? [];
		$namespace   = $route->namespace ?? $this->defaultNamespace;
		$version     = $route->version ?? $this->defaultVersion;

		$pathRegex     = $this->funcs->_regexPath($path);
		$fullPathRegex = $this->funcs->_regexPath($fullPath);

		$constructParams = [
			[
				'path'              => $path,
				'full_path'         => $fullPath,
				'method'            => $method,
				'callback_function' => $callback[1] ?? null,
			],
		];
		$constructParams = array_merge([
			$this->mainPath,
			$this->rootNamespace,
			$this->prefixEnv,
		], $constructParams);

		$callback = $this->prepareRouteCallback($callback, $constructParams);

		$routeNamespace = $namespace . '/' . $version;
//		$routeNamespace = $this->funcs->_regexPath($routeNamespace);

		register_rest_route(
			$routeNamespace,
			$fullPathRegex,
			[
				'methods' => strtoupper($method),
				'callback' => function(\WP_REST_Request $wpRestRequest) use ($callback, $path, $pathRegex, $fullPath, $fullPathRegex, $requestPath, $route) {
					$callParams = $this->getCallParams(
						$path,
						$fullPath,
						$requestPath,
						$callback[0],
						$callback[1],
						[
							'wpRestRequest' => $wpRestRequest,
							'route'         => $route,
						]
					);
					return $this->resolveAndCall($callback, $callParams);
				},
				'args' > [
//				    'id' => [
//					    'validate_callback' => function($param, $request, $key) {
//						    return is_numeric($param);
//					    }
//				    ],
				],
				'permission_callback' => function(\WP_REST_Request $wpRestRequest) use ($route, $middlewares) {
					static $permissionCallback = null;
					if ($permissionCallback !== null) return $permissionCallback;
					$permissionCallback = $this->isPassedMiddleware(
						$middlewares,
						$this->request,
						[
							'wpRestRequest' => $wpRestRequest,
							'route'         => $route,
						]
					);
					return $permissionCallback;
				},
			],
			true
		);
	}

}