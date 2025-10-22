<?php

namespace WPSPCORE\Traits;

trait TaxonomiesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->taxonomies();
		$this->hooks();
	}

	/*
     *
     */

	public function taxonomies() {}

	/*
	 *
	 */

	public function taxonomy($taxonomy, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10, $argsNumber = 1) {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$constructParams = [
				[
					'taxonomy'          => $taxonomy,
					'callback_function' => $callback[1] ?? null,
					'validation'        => $this->validation,
					'custom_properties' => $customProperties,
				],
			];
			$constructParams = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $constructParams);
			$callback = $this->prepareCallback($callback, $useInitClass, $constructParams);
			$callback[1] = 'init';
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($taxonomy) : $callback;
		}
	}

}