<?php

namespace WPSPCORE\Traits;

trait RewriteFrontPagesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->addQueryVars();

		$this->rewrite_front_pages();
		$this->hooks();
	}

	/*
	 *
	 */

	private function addQueryVars() {
		$this->filter('query_vars', function($query_vars) {
			$query_vars[] = 'is_rewrite';
			$query_vars[] = $this->funcs->_config('app.short_name') . '_rewrite_ident';
			for ($i = 1; $i <= 10; $i++) {
				$query_vars[] = $this->funcs->_config('app.short_name') . '_rewrite_group_' . $i;
			}
			return $query_vars;
		}, true, null, null, 10, 1);
	}

	/*
     *
     */

	public function rewrite_front_pages() {}

	/*
	 *
	 */

	public function get($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		if (!is_admin()
			&& !wp_doing_ajax()
			&& $this->request->isMethod('GET')
			&& $this->isPassedMiddleware($middlewares, $this->request)
		) {
			$constructParams = [
				[
					'path'              => $path,
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
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($path) : $callback;
		}
	}

	public function post($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		if (!is_admin() && !wp_doing_ajax()) {
			if ($this->request->isMethod('POST')) {
				$this->executeHiddenMethod($path, $callback, $useInitClass, $customProperties, $middlewares);
			}
		}
	}

	/*
	 *
	 */


	public function executeHiddenMethod($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		$requestPath = trim($this->request->getPathInfo(), '/\\');
		if (
			($this->request->get('page') == $path || preg_match('/' . $path . '/iu', $requestPath))
			&& $this->isPassedMiddleware($middlewares, $this->request)
		) {
			$constructParams = [
				[
					'path'              => $path,
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
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($path) : $callback;
		}
	}

}