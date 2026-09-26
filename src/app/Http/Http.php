<?php

namespace WPSPCORE\App\Http;

use Illuminate\Http\Client\Factory;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Http\Client\Factory
 * @mixin \Illuminate\Support\Facades\Http
 */
abstract class Http extends BaseInstances {

	private ?Factory $http;

	/*
	 *
	 */

	public function getHttp(): ?Factory {
		return $this->http;
	}

	public function setHttp() {
		$this->http = $this->funcs->_getApplication(\Illuminate\Http\Client\Factory::class);
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

		return $instance->getHttp()?->$method(...$arguments);
	}

}