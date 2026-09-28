<?php

namespace WPSPCORE\App\Bus;

use Illuminate\Contracts\Bus\Dispatcher;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Contracts\Bus\Dispatcher
 * @mixin \Illuminate\Support\Facades\Bus
 */
abstract class Bus extends BaseInstances {

	private ?Dispatcher $bus;

	/*
	 *
	 */

	public function getBus(): ?Dispatcher {
		return $this->bus;
	}

	public function setBus() {
		$this->bus = $this->funcs->_getApplication(Dispatcher::class);
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

		return $instance->getBus()?->$method(...$arguments);
	}

}