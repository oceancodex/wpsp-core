<?php

namespace WPSPCORE\App\Support\Facades;

use Illuminate\Foundation\Vite as IlluminateVite;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Vite
 */
abstract class Vite extends BaseInstances {

	private ?IlluminateVite $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateVite {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('vite');
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