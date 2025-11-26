<?php

namespace WPSPCORE\Routes\Apis;

use WPSPCORE\Routes\BaseRoute;
use WPSPCORE\Routes\RouteData;

/**
 * @method $this get(string $path, callable|array $callback, string|null $namespace = null, string|null $version = null)
 * @method $this post(string $path, callable|array $callback, string|null $namespace = null, string|null $version = null)
 * @method $this put(string $path, callable|array $callback, string|null $namespace = null, string|null $version = null)
 * @method $this patch(string $path, callable|array $callback, string|null $namespace = null, string|null $version = null)
 * @method $this delete(string $path, callable|array $callback, string|null $namespace = null, string|null $version = null)
 * @method $this options(string $path, callable|array $callback, string|null $namespace = null, string|null $version = null)
 * @method $this head(string $path, callable|array $callback, string|null $namespace = null, string|null $version = null)
 */
class Apis extends BaseRoute {

	public static string $defaultNamespace = 'wpsp';
	public static string $defaultVersion   = 'v1';

	/*
	 *
	 */

	public function beforeConstruct() {
		static::$defaultNamespace = static::$funcs->_getAppShortName();
	}

	/*
	 *
	 */

	public static function get($path, $callback, $args): RouteData {
		return $args['route'];
	}

	public static function post($path, $callback, $args): RouteData {
		echo '<pre style="background:white;z-index:9999;position:relative">'; print_r($args); echo '</pre>';
		return $args['route'];
	}

	/*
	 *
	 */

	public static function execute($method, $path, $callback, $args): RouteData {
		add_action('rest_api_init', function() use ($method, $path, $callback, $args) {
			static::registerRestRoute($method, $path, $callback, $args);
		});
		return $args['route'];
	}

	public static function registerRestRoute($method, $path, $callback, $args): void {
		$requestPath = trim(static::$request->getRequestUri(), '/\\');
		$fullPath = $args['route']->fullPath ?? $path;
		$middlewares = $args['route']->middlewares ?? [];

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

		$callback = static::prepareRouteCallback($callback, false, $constructParams);

		register_rest_route(
			($args['namespace'] ?? static::$defaultNamespace) . '/' . ($args['version'] ?? static::$defaultVersion),
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
				'args' => [
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