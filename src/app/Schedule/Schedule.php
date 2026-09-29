<?php

namespace WPSPCORE\App\Schedule;

use Illuminate\Console\Scheduling\Schedule as ScheduleCore;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Schedule
 */
abstract class Schedule extends BaseInstances {

	private ?ScheduleCore $facade;

	/*
	 *
	 */

	public function getFacade(): ?ScheduleCore {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(ScheduleCore::class);
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		$instance = static::wpspInstance();

		$underlineMethod = '_' . $method;
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		return $instance->getFacade()?->$method(...$arguments);
	}

}