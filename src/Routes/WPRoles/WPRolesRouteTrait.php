<?php

namespace WPSPCORE\Routes\WPRoles;

use WPSPCORE\Traits\HookRunnerTrait;

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