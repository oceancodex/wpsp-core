<?php

namespace WPSPCORE\App\Support\Facades;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\RateLimiter as IlluminateRateLimiter;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\RateLimiter
 */
abstract class RateLimiter extends BaseInstances {

	private ?IlluminateRateLimiter $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateRateLimiter {
		return $this->facade;
	}

	public function setFacade() {
		/** @var CacheManager $cacheManager */
		$this->facade = $this->funcs->_getApplication(IlluminateRateLimiter::class);
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