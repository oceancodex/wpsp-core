<?php

namespace WPSPCORE\Traits;

trait RolesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->roles();
		$this->hooks();
	}

	/*
     *
     */

	public function roles() {}

	/*
	 *
	 */

	public function role($role, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			if (is_array($callback)) {
				$customProperties = array_merge([$role, $callback[1]], ['custom_properties' => $customProperties ?? []]);
				$customProperties = array_merge([
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv()
				], $customProperties);
				$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
				$callback[1] = 'init';
				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($role) : $callback;
			}
			elseif (is_callable($callback)) {
				$callback();
			}
		}
	}

}