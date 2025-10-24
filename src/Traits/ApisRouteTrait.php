<?php

namespace WPSPCORE\Traits;

trait ApisRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->apis();
		$this->hooks();
		return $this;
	}

	public function initForRouterMap() {
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
		return $this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
	}

	public function post($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
		return $this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
	}

	public function put($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
		return $this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
	}

	public function delete($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
		return $this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
	}

	public function patch($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
		return $this->restApiInit($path, strtoupper(__FUNCTION__), $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
	}

	/*
	 *
	 */

	public function restApiInit($path, $method, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
		// Xây dựng full path
		$path = $this->buildFullPath($path);

		// Merge middlewares
		$allMiddlewares = $this->getFlattenedMiddlewares();
		if ($middlewares !== null) {
			$middlewares = array_merge($allMiddlewares, is_array($middlewares) ? $middlewares : [$middlewares]);
		}

		// Đánh dấu route để có thể name() sau này
		$this->markRouteForNaming($path);

		// Nếu đang build router map, chỉ lưu thông tin
		if ($this->isForRouterMap) {
			return $this;
		}
echo '<pre style="background:white;z-index:9999;position:relative">'; print_r($allMiddlewares); echo '</pre>';
		add_action('rest_api_init', function () use ($path, $method, $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version) {
			$this->registerRestRoute($path, $method, $callback, $useInitClass, $customProperties, $middlewares, $namespace, $version);
		});

		return $this;
	}

	public function registerRestRoute($path, $method, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $namespace = null, $version = null) {
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
			'callback'            => $this->prepareCallback($callback, $useInitClass, $constructParams),
			'args'                => [
//				'id' => [
//					'validate_callback' => function($param, $request, $key) {
//						return is_numeric($param);
//					}
//				],
			],
			'permission_callback' => function (\WP_REST_Request $request) use ($middlewares) {
				static $permissionCallback = null;
				if ($permissionCallback !== null) return $permissionCallback;
				$permissionCallback =  $this->isPassedMiddleware($middlewares, $request);
				return $permissionCallback;
			},
		],
		true);
	}

}