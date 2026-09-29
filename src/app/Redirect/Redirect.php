<?php

namespace WPSPCORE\App\Redirect;

use \Illuminate\Routing\Redirector;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Redirect
 */
abstract class Redirect extends BaseInstances {

	private ?Redirector $facade;

	/*
	 *
	 */

	public function getFacade(): ?Redirector {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('redirect');
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