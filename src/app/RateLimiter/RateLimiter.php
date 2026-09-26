<?php

namespace WPSPCORE\App\RateLimiter;

use Illuminate\Cache\CacheManager;
use WPSPCORE\BaseInstances;

abstract class RateLimiter extends BaseInstances {

	private ?\Illuminate\Cache\RateLimiter $rateLimiter;

	/*
	 *
	 */

	public function getRateLimiter(): ?\Illuminate\Cache\RateLimiter {
		return $this->rateLimiter;
	}

	public function setRateLimiter() {
		/** @var CacheManager $cacheManager */
		$this->rateLimiter = $this->funcs->_getApplication(\Illuminate\Cache\RateLimiter::class);
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

		return $instance->getRateLimiter()?->$method(...$arguments);
	}

}