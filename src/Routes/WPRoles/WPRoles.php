<?php

namespace WPSPCORE\Routes\WPRoles;

use WPSPCORE\Routes\BaseRoute;
use WPSPCORE\Routes\RouteData;

/**
 * @method $this role(string $role, callable|array $callback)
 */
class WPRoles extends BaseRoute {

	public function beforeConstruct(): void {}

	/*
	 *
	 */

	/**
	 * Những method thực tế Route được phép gọi.
	 */

	public static function role($role, $callback, $args = []): RouteData {
		return static::register(__FUNCTION__, $role, $callback, $args);
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
		$request     = static::$request;
		$requestPath = trim($request->getRequestUri(), '/\\');

		$middlewares = $route->middlewares;
		$path        = $role = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;

		$middlewareArgs    = ['role' => $role];
		$passedMiddlewares = static::isPassedMiddleware($middlewares, $request, $middlewareArgs);
		if ($passedMiddlewares) {
			$constructParams = [
				static::$mainPath,
				static::$rootNamespace,
				static::$prefixEnv,
				[
					'role'              => $role,
					'callback_function' => $callback[1] ?? null,
				],
			];

			$callback = static::prepareRouteCallback($callback, $constructParams);

			if (is_array($callback)) {
				$callback[1] = 'init';
				$callParams  = static::getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1]);
			}
			else {
				$callParams = static::getCallParams($path, $fullPath, $requestPath, $callback);
			}

			static::resolveAndCall($callback, $callParams);
		}
	}

}