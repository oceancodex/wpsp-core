<?php

namespace WPSPCORE\Traits;

trait TemplatesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
		$this->templates();
		$this->hooks();
	}

	/*
     *
     */

	public function templates() {}

	/*
	 *
	 */

	public function template($name, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $priority = 10, $argsNumber = 1): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$classArgs = array_merge([$name], $classArgs ?? []);
			$classArgs = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $classArgs);
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($name) : $callback;
		}
	}

}