<?php

namespace WPSPCORE\Traits;

trait RewriteFrontPagesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->addQueryVars();
		$this->rewrite_front_pages();
		$this->hooks();
	}

	public function initForRouterMap() {
		$this->rewrite_front_pages();
		return $this;
	}

	/*
	 *
	 */

	private function addQueryVars() {
		$this->filter('query_vars', function($query_vars) {
			$query_vars[] = 'is_rewrite';
			$query_vars[] = $this->funcs->_config('app.short_name') . '_rewrite_ident';
			for ($i = 1; $i <= 20; $i++) {
				$query_vars[] = $this->funcs->_config('app.short_name') . '_rewrite_group_' . $i;
			}
			return $query_vars;
		}, true, null, null, 10, 1);

		// Chặn redirect canonical cho các trang front page vì sử dụng "post_type" và "pagename" trong rewrite rules.
		$this->filter('redirect_canonical', function($redirect_url, $requested_url) {
			if (get_query_var('is_rewrite') == 'true') return false;
			return $redirect_url;
		}, false, null, null, 10, 2);
	}

	/*
     *
     */

	public function rewrite_front_pages() {}

	/*
	 *
	 */

	public function get($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
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
			return $this;
		}

		if (!is_admin()
			&& $this->request->isMethod('GET')
			&& $this->isPassedMiddleware($allMiddlewares, $this->request, ['path' => $fullPath, 'custom_properties' => $customProperties])
		) {
			$this->defineMark('REWRITE_FRONT_PAGE');
			$constructParams = [
				[
					'path'              => $fullPath,
					'callback_function' => $callback[1] ?? null,
					'custom_properties' => $customProperties,
				]
			];
			$constructParams = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
			], $constructParams);
			$callback         = $this->prepareCallback($callback, $useInitClass, $constructParams);
			$callback[1]      = 'init';
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($fullPath) : $callback;
		}

		// Reset middleware khi gọi xong function.
		$this->middlewareStack = [];

		return $this;
	}

	public function post($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		if (!is_admin()) {
			if ($this->request->isMethod('POST')) {
				$this->executeHiddenMethod($path, $callback, $useInitClass, $customProperties, $middlewares);
			}
		}

		// Reset middleware khi gọi xong function.
		$this->middlewareStack = [];

		return $this;
	}

	/*
	 *
	 */


	public function executeHiddenMethod($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
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
			return $this;
		}

		$requestPath = trim($this->request->getPathInfo(), '/\\');
		if (
			preg_match('/' . $fullPath . '/iu', $requestPath)
			&& $this->isPassedMiddleware($allMiddlewares, $this->request, ['path' => $fullPath, 'custom_properties' => $customProperties])
		) {
			$this->defineMark('REWRITE_FRONT_PAGE');
			$constructParams = [
				[
					'path'              => $fullPath,
					'callback_function' => $callback[1] ?? null,
					'custom_properties' => $customProperties,
				]
			];
			$constructParams = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
			], $constructParams);
			$callback         = $this->prepareCallback($callback, $useInitClass, $constructParams);
			$callback[1]      = 'init';
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($fullPath) : $callback;
		}
		return $this;
	}

}