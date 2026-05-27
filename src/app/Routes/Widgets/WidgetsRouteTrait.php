<?php

namespace WPSPCORE\App\Routes\Widgets;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait WidgetsRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->widgets();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function widgets();

}