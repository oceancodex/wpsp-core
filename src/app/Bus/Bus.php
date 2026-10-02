<?php

namespace WPSPCORE\App\Bus;

use Illuminate\Contracts\Bus\Dispatcher as IlluminateBus;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Bus
 */
abstract class Bus extends BaseInstances {

	private ?IlluminateBus $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateBus {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(IlluminateBus::class);
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