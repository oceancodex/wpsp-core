<?php

namespace WPSPCORE\App\Routes\Apis;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait ApisRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->apis();
		$this->hooks();
	}

	/*
	 *
	 */

	abstract public function apis();

}