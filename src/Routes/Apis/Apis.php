<?php

namespace WPSPCORE\Routes\Apis;

use WPSPCORE\Routes\BaseRoute;

/**
 * @method $this get(string $path, $callback)
 * @method $this post(string $path, $callback)
 */
class Apis extends BaseRoute {

	public static function get($path, $callback, $namespace = null, $version = null) {
		return static::restApiInit(__FUNCTION__, $path, $callback, $namespace, $version);
	}

	public static function post($path, $callback, $namespace = null, $version = null) {
		return static::restApiInit(__FUNCTION__, $path, $callback, $namespace, $version);
	}

	/*
	 *
	 */

	public static function restApiInit($method, $path, $callback, $namespace = null, $version = null) {
		$route = static::buildRoute($method, [$path, $callback, $namespace, $version]);
		add_action('rest_api_init', function() use ($route, $method, $path, $callback, $namespace, $version) {
			static::registerRestRoute($route, $path, $route->fullPath, $method, $callback, $namespace, $version);
		});
		return $route;
	}

	public static function registerRestRoute($route, $path, $fullPath, $method, $callback, $namespace = null, $version = null): void {
		$requestPath = trim(static::$request->getRequestUri(), '/\\');

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

		$callback   = static::prepareRouteCallback($callback, false, $constructParams);
		$callParams = static::getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1]);
		$callback   = static::resolveAndCall($callback, $callParams);

		register_rest_route(
			($namespace ?? 'wpsp') . '/' . ($version ?? 'v1'),
			$fullPath,
			[
				'methods'             => strtoupper($method),
				'callback'            => $callback,
				'args'                => [
//				    'id' => [
//					    'validate_callback' => function($param, $request, $key) {
//						    return is_numeric($param);
//					    }
//				    ],
				],
				'permission_callback' => function(\WP_REST_Request $request) use ($route, $path, $fullPath) {
					static $permissionCallback = null;
					if ($permissionCallback !== null) return $permissionCallback;
					$permissionCallback = static::isPassedMiddleware($route->middlewares, $request, [
						'path'            => $path,
						'full_path'       => $fullPath,
						'all_middlewares' => $route->middlewares,
					]);
					return $permissionCallback;
				},
			],
			true
		);
	}

}