<?php

namespace WPSPCORE\Traits;

trait TaxonomiesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
		$this->taxonomies();
	}

	/*
     *
     */

	public function taxonomies() {}

	/*
	 *
	 */

	public function taxonomy($taxonomy, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $priority = 10, $argsNumber = 0): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$classArgs = array_merge([$taxonomy], $classArgs ?? []);
			$classArgs = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $classArgs);
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($taxonomy) : $callback;
		}
	}

}