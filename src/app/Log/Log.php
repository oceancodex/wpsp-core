<?php

namespace WPSPCORE\App\Log;

use Illuminate\Log\LogManager as IlluminateLog;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Log
 */
abstract class Log extends BaseInstances {

	private ?IlluminateLog $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateLog {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('log');
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