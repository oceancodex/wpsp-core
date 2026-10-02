<?php

namespace WPSPCORE\App\Http;

use Illuminate\Http\Client\Factory as IlluminateHttp;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Http
 */
abstract class Http extends BaseInstances {

	private ?IlluminateHttp $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateHttp {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(IlluminateHttp::class);
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