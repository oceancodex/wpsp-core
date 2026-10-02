<?php

namespace WPSPCORE\App\Exceptions;

use Illuminate\Contracts\Debug\ExceptionHandler as IlluminateExceptions;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Exceptions
 */
abstract class Exceptions extends BaseInstances {

	private ?IlluminateExceptions $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateExceptions {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(IlluminateExceptions::class);
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