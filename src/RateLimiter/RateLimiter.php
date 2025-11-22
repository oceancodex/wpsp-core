<?php

namespace WPSPCORE\RateLimiter;

use Illuminate\Cache\CacheManager;
use WPSPCORE\Base\BaseInstances;

abstract class RateLimiter extends BaseInstances {

	private $rateLimiter;

	public function getRateLimiter() {
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

	public function __call($name, $arguments) {
		if (method_exists(static::instance(), $name)) {
			return static::instance()->$name(...$arguments);
		}
		else {
			return static::instance()->getRateLimiter()->$name(...$arguments);
		}
	}

	public static function __callStatic($name, $arguments) {
		if (method_exists(static::instance(), $name)) {
			return static::instance()->$name(...$arguments);
		}
		else {
			return static::instance()->getRateLimiter()->$name(...$arguments);
		}
	}

}