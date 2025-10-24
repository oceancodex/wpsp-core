<?php

namespace WPSPCORE\Traits;

trait ApisRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->apis();
		$this->hooks();
	}

	/*
	 *
	 */

	abstract public function apis();

	/*
	 *
	 */

	public function get($endpoint, $callback, $useInitClass = false, $customProperties = null, $middlewares = null, $permission_callback = '__return_true', $methods = 'GET') {
		// Xây dựng full path
		$fullPath = $this->buildFullPath($endpoint);

		// Merge middlewares
		$allMiddlewares = $this->getFlattenedMiddlewares();
		if ($middlewares !== null) {
			$allMiddlewares = array_merge($allMiddlewares, is_array($middlewares) ? $middlewares : [$middlewares]);
		}

		// Đánh dấu route để có thể name() sau này
		$this->markRouteForNaming($endpoint);

		// Nếu đang build router map, chỉ lưu thông tin
		if ($this->isForRouterMap) {
			return $this;
		}

		add_action('rest_api_init', function() use ($fullPath, $callback, $useInitClass, $customProperties, $allMiddlewares, $permission_callback, $methods) {
			if (!$this->isPassedMiddleware($allMiddlewares, $this->request)) {
				register_rest_route($this->funcs->_env('APP_SHORT_NAME', true), '/' . ltrim($fullPath, '/'), [
					'methods'             => $methods,
					'callback'            => function(\WP_REST_Request $request) {
						wp_send_json($this->funcs->_response(false, [], 'Access denied.', 403), 403);
					},
					'permission_callback' => $permission_callback,
				]);
				return;
			}

			$constructParams = [
				[
					'path'              => $fullPath,
					'callback_function' => $callback[1] ?? null,
					'validation'        => $this->validation,
					'custom_properties' => $customProperties,
				],
			];
			$constructParams = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
			], $constructParams);
			$callback        = $this->prepareCallback($callback, $useInitClass, $constructParams);

			register_rest_route($this->funcs->_env('APP_SHORT_NAME', true), '/' . ltrim($fullPath, '/'), [
				'methods'             => $methods,
				'callback'            => function(\WP_REST_Request $request) use ($callback, $fullPath) {
					$this->request = $request;
					if (isset($callback[0]) && isset($callback[1])) {
						return $callback[0]->{$callback[1]}($fullPath);
					}
					return $callback($fullPath);
				},
				'permission_callback' => $permission_callback,
			]);
		});

		return $this;
	}

	public function post($endpoint, $callback, $useInitClass = false, $customProperties = null, $middlewares = null, $permission_callback = '__return_true') {
		// Xây dựng full path
		$fullPath = $this->buildFullPath($endpoint);

		// Merge middlewares
		$allMiddlewares = $this->getFlattenedMiddlewares();
		if ($middlewares !== null) {
			$allMiddlewares = array_merge($allMiddlewares, is_array($middlewares) ? $middlewares : [$middlewares]);
		}

		// Đánh dấu route để có thể name() sau này
		$this->markRouteForNaming($endpoint);

		// Nếu đang build router map, chỉ lưu thông tin
		if ($this->isForRouterMap) {
			return $this;
		}

		add_action('rest_api_init', function() use ($fullPath, $callback, $useInitClass, $customProperties, $allMiddlewares, $permission_callback) {
			if (!$this->isPassedMiddleware($allMiddlewares, $this->request)) {
				register_rest_route($this->funcs->_env('APP_SHORT_NAME', true), '/' . ltrim($fullPath, '/'), [
					'methods'             => 'POST',
					'callback'            => function(\WP_REST_Request $request) {
						wp_send_json($this->funcs->_response(false, [], 'Access denied.', 403), 403);
					},
					'permission_callback' => $permission_callback,
				]);
				return;
			}

			$constructParams = [
				[
					'path'              => $fullPath,
					'callback_function' => $callback[1] ?? null,
					'validation'        => $this->validation,
					'custom_properties' => $customProperties,
				],
			];
			$constructParams = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
			], $constructParams);
			$callback        = $this->prepareCallback($callback, $useInitClass, $constructParams);

			register_rest_route($this->funcs->_env('APP_SHORT_NAME', true), '/' . ltrim($fullPath, '/'), [
				'methods'             => 'POST',
				'callback'            => function(\WP_REST_Request $request) use ($callback, $fullPath) {
					$this->request = $request;
					if (isset($callback[0]) && isset($callback[1])) {
						return $callback[0]->{$callback[1]}($fullPath);
					}
					return $callback($fullPath);
				},
				'permission_callback' => $permission_callback,
			]);
		});

		return $this;
	}

	public function put($endpoint, $callback, $useInitClass = false, $customProperties = null, $middlewares = null, $permission_callback = '__return_true') {
		return $this->get($endpoint, $callback, $useInitClass, $customProperties, $middlewares, $permission_callback, 'PUT');
	}

	public function patch($endpoint, $callback, $useInitClass = false, $customProperties = null, $middlewares = null, $permission_callback = '__return_true') {
		return $this->get($endpoint, $callback, $useInitClass, $customProperties, $middlewares, $permission_callback, 'PATCH');
	}

	public function delete($endpoint, $callback, $useInitClass = false, $customProperties = null, $middlewares = null, $permission_callback = '__return_true') {
		return $this->get($endpoint, $callback, $useInitClass, $customProperties, $middlewares, $permission_callback, 'DELETE');
	}

}