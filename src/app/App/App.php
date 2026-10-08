<?php

namespace WPSPCORE\App\App;

use Illuminate\Foundation\Application as IlluminateApplication;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\App
 */
abstract class App extends BaseInstances {

	private ?IlluminateApplication $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateApplication {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication();
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