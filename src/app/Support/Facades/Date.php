<?php

namespace WPSPCORE\App\Support\Facades;

use Illuminate\Support\DateFactory as IlluminateDate;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Date
 */
abstract class Date extends BaseInstances {

	private ?IlluminateDate $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateDate {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('date');
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