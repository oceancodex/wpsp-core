<?php

namespace WPSPCORE\App\Cache;

use Illuminate\Cache\CacheManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Cache
 */
abstract class Cache extends BaseInstances {

	private ?CacheManager $facade;

	/*
	 *
	 */

	public function getFacade(): ?CacheManager {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('cache');
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