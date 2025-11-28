<?php

namespace WPSPCORE\Traits;

trait HookRunnerTrait {

	public function hooks() {
		$this->actions();
		$this->filters();
	}

	/*
	 *
	 */

	public function actions() {}

	public function filters() {}

	/*
	 *
	 */

	public static function hook($route) {
		$method       = $route->method;
		$path         = $route->path;
		$fullPath     = $route->fullPath;
		$callback     = $route->callback;
		$middlewares  = $route->middlewares;
		$priority     = $route->args['priority'] ?? 10;
		$acceptedArgs = $route->args['accepted_args'] ?? 1;
		if (static::isPassedMiddleware($route->middlewares, static::$request, [
			'method'      => $method,
			'path'        => $path,
			'middlewares' => $middlewares,
		])) {

			$constructParams = [
				[
					'path'      => $path,
					'full_path' => $fullPath,
					'method'    => $method,
				],
			];
			$constructParams = array_merge([
				static::$mainPath,
				static::$rootNamespace,
				static::$prefixEnv,
			], $constructParams);

			$requestPath = trim(static::$request->getRequestUri(), '/\\');
			$callback    = static::prepareRouteCallback($callback, $constructParams);
			$callParams  = static::getCallParams($path, $fullPath, $requestPath, $callback);
			$callback    = static::resolveCallback($callback, $callParams);
			if ($method == 'action') {
				add_action($path, $callback, $priority, $acceptedArgs);
			}
			elseif ($method == 'filter') {
				add_filter($path, $callback, $priority, $acceptedArgs);
			}
		}
	}

	public static function action($hook, $callback, $priority = 10, $acceptedArgs = 1) {
		return static::buildRoute(__FUNCTION__, [$hook, $callback, ['priority' => $priority, 'accepted_args' => $acceptedArgs]]);
	}

	public static function filter($hook, $callback, $priority = 10, $acceptedArgs = 1) {
		return static::buildRoute(__FUNCTION__, [$hook, $callback, ['priority' => $priority, 'accepted_args' => $acceptedArgs]]);
	}

	/*
	 *
	 */

	public function remove_hook($route) {
		if ($this->isPassedMiddleware($middlewares, $this->request, [
			'type'              => $type,
			'hook'              => $hook,
			'middlewares'   => $middlewares,
			'custom_properties' => $customProperties,
		])) {
			$callback = $this->prepareRouteCallback($callback, $useInitClass, $customProperties);
			if ($type == 'action') {
				remove_action($hook, $callback, $priority);
			}
			elseif ($type == 'filter') {
				remove_filter($hook, $callback, $priority);
			}
		}
	}

	public function remove_action($hook, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10) {
		$this->remove_hook('action', $hook, $callback, $useInitClass, $customProperties, $middlewares, $priority);
	}

	public function remove_filter($hook, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10) {
		$this->remove_hook('filter', $hook, $callback, $useInitClass, $customProperties, $middlewares, $priority);
	}

}