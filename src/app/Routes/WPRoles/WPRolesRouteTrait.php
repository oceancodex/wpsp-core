<?php

namespace WPSPCORE\App\Routes\WPRoles;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait WPRolesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->roles();
		$this->hooks();
	}

	/*
     *
     */

	public function roles() {}

}