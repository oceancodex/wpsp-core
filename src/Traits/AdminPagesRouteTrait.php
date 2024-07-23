<?php

namespace WPSPCORE\Traits;

trait AdminPagesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
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

	public function group($callback, $middlewares = null): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$callback();
		}
	}

	/*
	 *
	 */

	public function get($path, $callback, $useInitClass = false, $classArgs = [], $middlewares = null): void {
		if (!wp_doing_ajax() && $this->isPassedMiddleware($middlewares, $this->request)) {
			$classArgs = array_merge([$path], $classArgs ?? []);
			$classArgs = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $classArgs);
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($path) : $callback;
		}
	}

	public function post($path, $callback, $useInitClass = false, $classArgs = [], $middlewares = null): void {
		if (!wp_doing_ajax() && $this->request->isMethod('POST')) {
			$requestPath = trim($this->request->getPathInfo(), '/\\');
			if (
				($this->request->get('page') == $path || preg_match('/' . $path . '/iu', $requestPath))
				&& $this->isPassedMiddleware($middlewares, $this->request)
			) {
				$classArgs = array_merge([$path], $classArgs ?? []);
				$classArgs = array_merge([
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv()
				], $classArgs);
				$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($path) : $callback;
			}
		}
	}

}