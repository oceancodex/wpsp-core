<?php

namespace WPSPCORE\Traits;

trait RolesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function beforeConstruct() {
		$this->extraParams = [
			'prepare_funcs' => true
		];
	}

	/*
     *
     */

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
				$constructParams = [
					[
						'role'              => $role,
						'callback_function' => $callback[1] ?? null,
						'custom_properties' => $customProperties,
					],
				];
				$constructParams = array_merge([
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
				], $constructParams);
				$callback = $this->prepareCallback($callback, $useInitClass, $constructParams);
				$callback[1] = 'init';
				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($role) : $callback;
			}
			elseif (is_callable($callback)) {
				$callback();
			}
		}
	}

}