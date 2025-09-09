<?php

namespace WPSPCORE\Traits;

trait ApisRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
		$this->apis();
		$this->hooks();
	}

	/*
	 *
	 */

	public function apis() {}

	/*
	 *
	 */

	public function get($path, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $namespace = null, $version = null): void {
		$this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $classArgs, $middlewares, $namespace, $version);
	}

	public function post($path, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $namespace = null, $version = null): void {
		$this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $classArgs, $middlewares, $namespace, $version);
	}

	public function put($path, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $namespace = null, $version = null): void {
		$this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $classArgs, $middlewares, $namespace, $version);
	}

	public function delete($path, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $namespace = null, $version = null): void {
		$this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $classArgs, $middlewares, $namespace, $version);
	}

	public function patch($path, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $namespace = null, $version = null): void {
		$this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $classArgs, $middlewares, $namespace, $version);
	}

	/*
	 *
	 */

	public function restApiInit($path, $method, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $namespace = null, $version = null): void {
		add_action('rest_api_init', function () use ($path, $method, $callback, $useInitClass, $classArgs, $middlewares, $namespace, $version) {
			$this->registerRestRoute($path, $method, $callback, $useInitClass, $classArgs, $middlewares, $namespace, $version);
		});
	}

	public function registerRestRoute($path, $method, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $namespace = null, $version = null): void {
		register_rest_route(($namespace ?? $this->funcs->_config('app.short_name')) . '/' . ($version ?? 'v1'), $path, [
			'methods'             => $method,
			'callback'            => $this->prepareCallback($callback, $useInitClass, $classArgs),
			'args'                => [
//				'id' => [
//					'validate_callback' => function($param, $request, $key) {
//						return is_numeric($param);
//					}
//				],
			],
			'permission_callback' => function (\WP_REST_Request $request) use ($middlewares) {
				return $this->isPassedMiddleware($middlewares, $request);
			},
		]);
	}

}