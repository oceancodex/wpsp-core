<?php

namespace WPSPCORE\App\Gate;

use Illuminate\Contracts\Auth\Access\Gate as IlluminateGate;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Gate
 */
abstract class Gate extends BaseInstances {

	private ?IlluminateGate $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateGate {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(IlluminateGate::class);
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