<?php

namespace WPSPCORE\App\Http;

use Illuminate\Http\Client\Factory;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Http\Client\Factory
 * @mixin \Illuminate\Support\Facades\Http
 */
abstract class Http extends BaseInstances {

	private ?Factory $facade;

	/*
	 *
	 */

	public function getFacade(): ?Factory {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(Factory::class);
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