<?php

namespace WPSPCORE\App\ParallelTesting;

use Illuminate\Testing\ParallelTesting as IlluminateParallelTesting;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\ParallelTesting
 */
abstract class ParallelTesting extends BaseInstances {

	private ?IlluminateParallelTesting $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateParallelTesting {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(IlluminateParallelTesting::class);
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