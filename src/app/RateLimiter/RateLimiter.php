<?php

namespace WPSPCORE\App\RateLimiter;

use Illuminate\Cache\CacheManager;
use WPSPCORE\App\BaseInstances;

abstract class RateLimiter extends BaseInstances {

	private \Illuminate\Cache\RateLimiter $rateLimiter;

	public function getRateLimiter(): \Illuminate\Cache\RateLimiter {
		return $this->rateLimiter;
	}

	public function setRateLimiter(): void {
		/** @var CacheManager $cacheManager */
		$cacheManager      = $this->funcs->getApplication('cache');
		$cacheStore        = $cacheManager->store();
		$this->rateLimiter = new \Illuminate\Cache\RateLimiter($cacheStore);
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		if (method_exists(static::instance(), $method)) {
			return static::instance()->$method(...$arguments);
		}
		else {
			return static::instance()->getRateLimiter()->$method(...$arguments);
		}
	}

}