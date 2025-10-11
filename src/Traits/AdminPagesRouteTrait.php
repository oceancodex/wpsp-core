<?php

namespace WPSPCORE\Traits;

trait AdminPagesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->admin_pages();
		$this->hooks();
	}

	/*
     *
     */

	public function admin_pages() {}

	/*
	 *
	 */

	public function get($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		if (
			is_admin()
			&& !wp_doing_ajax()
			&& $this->isPassedMiddleware($middlewares, $this->request)
		) {
			$customProperties = array_merge([$path, $callback[1]], ['custom_properties' => $customProperties ?? []]);
			$customProperties = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
			], $customProperties);
			$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
			$callback[1] = 'init';
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($path) : $callback;
		}
	}

	public function post($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		if (is_admin() && !wp_doing_ajax()) {
			if ($this->request->isMethod('POST')) {
				$this->executeHiddenMethod($path, $callback, $useInitClass, $customProperties, $middlewares);
			}
		}
	}

	/*
	 *
	 */

	public function executeHiddenMethod($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		$requestPath = trim($this->request->getRequestUri(), '/\\');
		if (
			($this->request->get('page') == $path || preg_match('/' . preg_quote($path, '/') . '/iu', $requestPath))
			&& $this->isPassedMiddleware($middlewares, $this->request)
		) {
			$customProperties = array_merge([$path], ['custom_properties' => $customProperties ?? []]);
			$customProperties = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
			], $customProperties);
			$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($path) : $callback;
		}
	}

}