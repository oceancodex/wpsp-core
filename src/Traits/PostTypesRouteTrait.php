<?php

namespace WPSPCORE\Traits;

trait PostTypesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
		$this->post_types();
		$this->hooks();
	}

	/*
     *
     */

	public function post_types() {}

	/*
	 *
	 */

	public function post_type($postType, $callback, $useInitClass = false, $classArgs = [], $middlewares = null): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			if (is_array($callback)) {
				$classArgs = array_merge([$postType], $classArgs ?? []);
				$classArgs = array_merge([
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv()
				], $classArgs);
				$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($postType) : $callback;
			}
			elseif (is_callable($callback)) {
				$callback();
			}
		}
	}

}