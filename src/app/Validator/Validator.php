<?php

namespace WPSPCORE\App\Validator;

use Illuminate\Validation\Factory as ValidatorCore;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Validator
 */
abstract class Validator extends BaseInstances {

	private ?ValidatorCore $facade;

	/*
	 *
	 */

	public function getFacade(): ?ValidatorCore {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('validator');
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