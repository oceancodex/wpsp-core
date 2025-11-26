<?php

namespace WPSPCORE\Routes\Templates;

use WPSPCORE\Traits\HookRunnerTrait;
use WPSPCORE\Traits\RouteTrait;

trait TemplatesRouteTrait {

	use HookRunnerTrait, RouteTrait;

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
		if ($this->isPassedMiddleware($middlewares, $this->request, ['name' => $name, 'custom_properties' => $customProperties])) {
			$constructParams = [
				[
					'name'              => $name,
					'callback_function' => $callback[1] ?? null,
					'custom_properties' => $customProperties,
				],
			];
			$constructParams = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $constructParams);
			$callback = $this->prepareRouteCallback($callback, $useInitClass, $constructParams);
			$callback[1] = 'init';
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($name) : $callback;
		}
	}

}