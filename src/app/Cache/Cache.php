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

	public function getCache(): CacheManager {
		return $this->cache;
	}

	public function setCache(): void {
		$this->cache = $this->funcs->getApplication('cache');
	}

	/*
	 *
	 */

	public function __call($name, $arguments) {
		if (method_exists(static::instance(), $name)) {
			return static::instance()->$name(...$arguments);
		}
		else {
			return static::instance()->getCache()->$name(...$arguments);
		}
	}

	public static function __callStatic($name, $arguments) {
		if (method_exists(static::instance(), $name)) {
			return static::instance()->$name(...$arguments);
		}
		else {
			return static::instance()->getCache()->$name(...$arguments);
		}
	}

}