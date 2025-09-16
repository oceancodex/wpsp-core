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

	public function taxonomy($taxonomy, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10, $argsNumber = 1): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$customProperties = array_merge([$taxonomy], $customProperties ?? []);
			$customProperties = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $customProperties);
			$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($taxonomy) : $callback;
		}
	}

}