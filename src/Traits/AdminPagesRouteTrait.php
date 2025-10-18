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
		) {
			$requestPath = trim($this->request->getRequestUri(), '/\\');
			if (
				($this->request->get('page') == $path || preg_match('/' . preg_quote($path, '/') . '/iu', $requestPath) || $callback[1] == 'index')
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
				$callback = $this->prepareCallback($callback, $useInitClass, $constructParams);
				if ($callback[1] == 'index') $callback[1] = 'init';
				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($path) : $callback;
			}
			else {
				$currentPath = $this->request->getRequestUri();
				if (preg_match('/'.preg_quote($path, '/').'/iu', $currentPath)) {
					wp_die('Access denied.');
				}
			}
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
		$screenOptions = $this->request->get('wp_screen_options');
		if ($screenOptions) {
			return;
		}

		$requestPath = trim($this->request->getRequestUri(), '/\\');
		if (
			($this->request->get('page') == $path || preg_match('/' . preg_quote($path, '/') . '/iu', $requestPath))
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
			$callback = $this->prepareCallback($callback, $useInitClass, $constructParams);
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($path) : $callback;
		}
	}

}