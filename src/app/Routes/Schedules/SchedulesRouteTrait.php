<?php

namespace WPSPCORE\App\Routes\Schedules;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait SchedulesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->intervals();
		$this->schedules();
		$this->hooks();
	}

	/*
	 *
	 */

	abstract public function intervals();

	abstract public function schedules();

}