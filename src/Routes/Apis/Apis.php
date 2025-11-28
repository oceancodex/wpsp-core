<?php

namespace WPSPCORE\Routes\Apis;

use WPSPCORE\Routes\BaseRoute;
use WPSPCORE\Routes\RouteData;

/**
 * @method $this get(string $path, callable|array $callback)
 * @method $this post(string $path, callable|array $callback)
 * @method $this put(string $path, callable|array $callback)
 * @method $this patch(string $path, callable|array $callback)
 * @method $this delete(string $path, callable|array $callback)
 * @method $this options(string $path, callable|array $callback)
 * @method $this head(string $path, callable|array $callback)
 */
class Apis extends BaseRoute {

	public static string $defaultNamespace = 'wpsp';
	public static string $defaultVersion   = 'v1';

	/*
	 *
	 */

	public function beforeConstruct(): void {
		static::$defaultNamespace = static::$funcs->_getAppShortName();
	}

	/**
	 * Những method thực tế Route được phép gọi.
	 */

	public static function get($path, $callback, $args = []): RouteData {
		return static::register(__FUNCTION__, $path, $callback, $args);
	}

	public static function post($path, $callback, $args = []): RouteData {
		return static::register(__FUNCTION__, $path, $callback, $args);
	}

	/*
	 *
	 */

	/**
	 * Đăng ký route với Route Manager.
	 */
	public static function register($method, $path, $callback, $args = []): RouteData {
		return static::buildRoute($method, [$path, $callback, $args]);
	}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public static function execute($route): void {
		add_action('rest_api_init', function() use ($route) {
			static::registerRestRoute($route);
		});
	}

	/*
	 *
	 */

	public static function registerRestRoute($route): void {
		$requestPath = trim(static::$request->getRequestUri(), '/\\');
		$method      = $route->method;
		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares ?? [];
		$namespace   = $route->namespace ?? static::$defaultNamespace;
		$version     = $route->version ?? static::$defaultVersion;

		$path     = static::convertPathToRegex($path);
		$fullPath = static::convertPathToRegex($fullPath);

		$constructParams = [
			[
				'path'              => $path,
				'full_path'         => $fullPath,
				'method'            => $method,
				'callback_function' => $callback[1] ?? null,
			],
		];
		$constructParams = array_merge([
			static::$mainPath,
			static::$rootNamespace,
			static::$prefixEnv,
		], $constructParams);

		$callback = static::prepareRouteCallback($callback, $constructParams);

		register_rest_route(
			$namespace . '/' . $version,
			$fullPath,
			[
				'methods' => strtoupper($method),
				'callback' => function(\WP_REST_Request $wpRestRequest) use ($callback, $path, $fullPath, $requestPath) {
					$callParams = static::getCallParams(
						$path,
						$fullPath,
						$requestPath,
						$callback[0],
						$callback[1],
						['wpRestRequest' => $wpRestRequest]
					);
					return static::resolveAndCall($callback, $callParams);
				},
				'args' > [
//				    'id' => [
//					    'validate_callback' => function($param, $request, $key) {
//						    return is_numeric($param);
//					    }
//				    ],
				],
				'permission_callback' => function(\WP_REST_Request $request) use ($path, $fullPath, $middlewares) {
					static $permissionCallback = null;
					if ($permissionCallback !== null) return $permissionCallback;
					$permissionCallback = static::isPassedMiddleware($middlewares, $request, [
						'path'        => $path,
						'full_path'   => $fullPath,
						'middlewares' => $middlewares,
					]);
					return $permissionCallback;
				},
			],
			true
		);
	}

}