<?php

namespace WPSPCORE\Traits;

trait ApisRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->customProperties();
		$this->apis();
		$this->hooks();
		return $this;
	}

	public function initForRouterMap() {
		$this->customProperties();
		$this->apis();
		return $this;
	}

	/*
	 *
	 */

	abstract public function apis();

	/*
	 *
	 */

	public function get($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
		$this->currentCallMethod = 'route';
		$this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
		$this->middlewareStack = [];
		return $this;
	}

	public function post($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
		$this->currentCallMethod = 'route';
		$this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
		$this->middlewareStack = [];
		return $this;
	}

	public function put($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
		$this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
		$this->middlewareStack = [];
		return $this;
	}

	public function delete($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
		$this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
		$this->middlewareStack = [];
		return $this;
	}

	public function patch($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
		$this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
		$this->middlewareStack = [];
		return $this;
	}

	/*
	 *
	 */

	public function restApiInit($path, $method, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null): void {
		// Xây dựng full path
		$fullPath = $this->buildFullPath($path);

		// Merge middlewares
		$allMiddlewares = $this->getFlattenedMiddlewares();
		if ($middlewares !== null) {
			$allMiddlewares = array_merge($allMiddlewares, is_array($middlewares) ? $middlewares : [$middlewares]);
		}

		// Đánh dấu route để có thể name() sau này
		$this->markRouteForNaming($path);

		// Nếu đang build router map, chỉ lưu thông tin
		if ($this->isForRouterMap) {
			return;
		}

		add_action('rest_api_init', function () use ($fullPath, $method, $callback, $useInitClass, $customProperties, $allMiddlewares, $namespace, $version) {
			$this->registerRestRoute($fullPath, $method, $callback, $useInitClass, $customProperties, $allMiddlewares, $namespace, $version);
		});
	}

	public function registerRestRoute($path, $method, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null): void {
		$constructParams = [
			[
				'path'              => $path,
				'method'            => $method,
				'callback_function' => $callback[1] ?? null,
				'custom_properties' => $customProperties,
			]
		];
		$constructParams = array_merge([
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv(),
		], $constructParams);
		register_rest_route(($namespace ?? $this->funcs->_config('app.short_name')) . '/' . ($version ?? 'v1'), $path, [
			'methods'             => $method,
			'callback'            => $this->prepareRouteCallback($callback, $useInitClass, $constructParams),
			'args'                => [
//				'id' => [
//					'validate_callback' => function($param, $request, $key) {
//						return is_numeric($param);
//					}
//				],
			],
			'permission_callback' => function (\WP_REST_Request $request) use ($middlewares, $path, $customProperties) {
				static $permissionCallback = null;
				if ($permissionCallback !== null) return $permissionCallback;
				$permissionCallback =  $this->isPassedMiddleware($middlewares, $request, ['path' => $path, 'custom_properties' => $customProperties]);
				return $permissionCallback;
			},
		],
		true);
	}

}