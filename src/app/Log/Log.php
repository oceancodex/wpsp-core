<?php

namespace WPSPCORE\App\Log;

use Illuminate\Log\LogManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Log
 * @mixin \Illuminate\Log\LogManager
 */
abstract class Log extends BaseInstances {

	private LogManager $log;

	/*
	 *
	 */

	public function getLog(): LogManager {
		return $this->log;
	}

	public function setLog(): void {
		$this->log = $this->funcs->getApplication('log');
	}

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

		return $instance->getLog()->$method(...$arguments);
	}

}