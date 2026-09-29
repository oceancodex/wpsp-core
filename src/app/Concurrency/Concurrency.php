<?php

namespace WPSPCORE\App\Concurrency;

use Illuminate\Concurrency\ConcurrencyManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Concurrency
 */
abstract class Concurrency extends BaseInstances {

	private ?ConcurrencyManager $facade;

	/*
	 *
	 */

	public function getFacade(): ?ConcurrencyManager {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(ConcurrencyManager::class);
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