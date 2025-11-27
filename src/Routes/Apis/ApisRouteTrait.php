<?php

namespace WPSPCORE\Routes\Apis;

use WPSPCORE\Traits\HookRunnerTrait;

trait ApisRouteTrait {

	use HookRunnerTrait;

	public function init() {
		$this->apis();
		$this->hooks();
	}

	/*
	 *
	 */

	abstract public function apis();

}