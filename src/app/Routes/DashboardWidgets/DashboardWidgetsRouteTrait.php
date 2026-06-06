<?php

namespace WPSPCORE\App\Routes\DashboardWidgets;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait DashboardWidgetsRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->dashboard_widgets();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function dashboard_widgets();

}