<?php

namespace WPSPCORE\App\ParallelTesting;

use Illuminate\Testing\ParallelTesting as ParallelTestingCore;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\ParallelTesting
 */
abstract class ParallelTesting extends BaseInstances {

	private ?ParallelTestingCore $facade;

	/*
	 *
	 */

	public function getFacade(): ?ParallelTestingCore {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(ParallelTestingCore::class);
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