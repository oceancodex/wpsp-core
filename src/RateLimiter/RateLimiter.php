<?php

namespace WPSPCORE\RateLimiter;

use WPSPCORE\Base\BaseInstances;

abstract class RateLimiter extends BaseInstances {

	private $rateLimiter;

	public function getRateLimiter() {
		return $this->rateLimiter;
	}

	public function setRateLimiter(): void {
		$cacheStore = ($this->funcs->getApplication('cache'))->store();
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