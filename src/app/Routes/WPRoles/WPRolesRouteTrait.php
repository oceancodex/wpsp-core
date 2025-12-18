<?php

namespace WPSPCORE\App\Routes\WPRoles;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait WPRolesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->wp_roles();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function wp_roles();

}