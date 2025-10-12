<?php

namespace WPSPCORE\Traits;

trait NavLocationsRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->nav_locations();
		$this->hooks();
	}

	/*
     *
     */

	public function nav_locations() {}

	/*
	 *
	 */

	public function nav_location($location, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		$constructParams = [
			[
				'location'          => $location,
				'callback_function' => $callback[1] ?? null,
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
		isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($location) : $callback;
	}

}