<?php

namespace WPSPCORE\App\Routes\Customizers;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait CustomizersRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->customizers();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function customizers();

}