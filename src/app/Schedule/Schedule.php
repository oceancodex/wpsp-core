<?php

namespace WPSPCORE\App\Schedule;

use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Console\Scheduling\Schedule
 * @mixin \Illuminate\Support\Facades\Schedule
 */
abstract class Schedule extends BaseInstances {

	private \Illuminate\Console\Scheduling\Schedule $schedule;

	/*
	 *
	 */

	public function getSchedule(): \Illuminate\Console\Scheduling\Schedule {
		return $this->funcs->getApplication(\Illuminate\Console\Scheduling\Schedule::class);
//		return $this->schedule;
	}

//	public function setSchedule() {
//		$this->schedule = $this->funcs->getApplication('schedule');
//	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		$instance = static::instance();

		$underlineMethod = '_' . $method;
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		return $instance->getSchedule()->$method(...$arguments);
	}

}