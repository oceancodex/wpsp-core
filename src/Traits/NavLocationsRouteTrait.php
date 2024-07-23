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

	public function nav_location($location, $callback, $useInitClass = false, $classArgs = [], $middlewares = null): void {
		$classArgs = array_merge([$location], $classArgs ?? []);
		$classArgs = array_merge([
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv()
		], $classArgs);
		$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
		isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($location) : $callback;
	}

}