<?php

namespace WPSPCORE\App\RateLimiter;

use Illuminate\Cache\RateLimiter as RateLimiterCore;
use Illuminate\Cache\CacheManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\RateLimiter
 */
abstract class RateLimiter extends BaseInstances {

	private ?RateLimiterCore $facade;

	/*
	 *
	 */

	public function getFacade(): ?RateLimiterCore {
		return $this->facade;
	}

	public function setFacade() {
		/** @var CacheManager $cacheManager */
		$this->facade = $this->funcs->_getApplication(RateLimiterCore::class);
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