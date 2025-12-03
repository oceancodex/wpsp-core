<?php

namespace WPSPCORE\App\Cache;

use Illuminate\Cache\CacheManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Cache\CacheManager
 * @mixin \Illuminate\Support\Facades\Cache
 */
abstract class Cache extends BaseInstances {

	private CacheManager $cache;

	/*
	 *
	 */

	public function getCache(): CacheManager {
		return $this->cache;
	}

	public function setCache(): void {
		$this->cache = $this->funcs->getApplication('cache');
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		$instance = static::instance();

		$underlineMethod = '_' . $method;
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		return $instance->getCache()->$method(...$arguments);
	}

}