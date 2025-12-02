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

	/*
	 *
	 */

	public function interval($name, $interval, $display) {
		add_filter('cron_schedules', function($schedules) use ($name, $interval, $display) {
			$schedules[$name] = [
				'interval' => $interval,
				'display'  => $display
			];
			return $schedules;
		});
	}

}