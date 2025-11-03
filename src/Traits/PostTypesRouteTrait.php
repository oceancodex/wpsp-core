<?php

namespace WPSPCORE\Traits;

trait PostTypesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
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

	public function post_type($postType, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			if (is_array($callback)) {
				$constructParams = [
					[
						'post_type'         => $postType,
						'callback_function' => $callback[1] ?? null,
						'custom_properties' => $customProperties,
					],
				];
				$constructParams = array_merge([
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
				], $constructParams);
				$callback        = $this->prepareCallback($callback, $useInitClass, $constructParams);
				$callback[1]     = 'init';
				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($postType) : $callback;
			}
			elseif (is_callable($callback)) {
				$callback();
			}
		}
	}

}