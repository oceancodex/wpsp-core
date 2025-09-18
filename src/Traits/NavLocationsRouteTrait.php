<?php

namespace WPSPCORE\Traits;

trait NavLocationsRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
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

	public function nav_location($location, $callback, $useInitClass = false, $customProperties = [], $middlewares = null): void {
		$customProperties = array_merge([$location, $callback[1]], ['custom_properties' => $customProperties ?? []]);
		$customProperties = array_merge([
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv()
		], $customProperties);
		$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
		$callback[1] = 'init';
		isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($location) : $callback;
	}

}