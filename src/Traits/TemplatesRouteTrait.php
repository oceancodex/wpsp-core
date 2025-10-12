<?php

namespace WPSPCORE\Traits;

trait TemplatesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
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

	public function template($name, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10, $argsNumber = 1) {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$constructParams = [
				[
					'name'              => $name,
					'callback_function' => $callback[1],
					'custom_properties' => $customProperties,
				],
			];
			$constructParams = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $constructParams);
			$callback = $this->prepareCallback($callback, $useInitClass, $constructParams);
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($name) : $callback;
		}
	}

}