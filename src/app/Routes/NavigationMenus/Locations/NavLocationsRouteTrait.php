<?php

namespace WPSPCORE\App\Routes\NavigationMenus\Locations;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait NavLocationsRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->nav_locations();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function nav_locations();

}