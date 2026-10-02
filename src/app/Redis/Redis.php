<?php

namespace WPSPCORE\App\Redis;

use \Illuminate\Redis\RedisManager as IlluminateRedis;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Redis
 */
abstract class Redis extends BaseInstances {

	private ?IlluminateRedis $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateRedis {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('redis');
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