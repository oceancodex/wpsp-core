<?php

namespace WPSPCORE\App\Process;

use Illuminate\Process\Factory;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Process
 */
abstract class Process extends BaseInstances {

	private ?Factory $facade;

	/*
	 *
	 */

	public function getFacade(): ?Factory {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('process');
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