<?php

namespace WPSPCORE\App\Request;

use \Illuminate\Http\Request as RequestCore;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Request
 */
abstract class Request extends BaseInstances {

	private ?RequestCore $facade;

	/*
	 *
	 */

	public function getFacade(): ?RequestCore {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('redis');
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