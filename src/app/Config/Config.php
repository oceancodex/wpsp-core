<?php

namespace WPSPCORE\App\Config;

use Illuminate\Config\Repository;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Config
 */
abstract class Config extends BaseInstances {

	private ?Repository $facade;

	/*
	 *
	 */

	public function getFacade(): ?Repository {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('config');
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