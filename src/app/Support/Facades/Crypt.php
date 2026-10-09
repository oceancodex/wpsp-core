<?php

namespace WPSPCORE\App\Support\Facades;

use Illuminate\Encryption\Encrypter as IlluminateCrypt;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Crypt
 */
abstract class Crypt extends BaseInstances {

	private ?IlluminateCrypt $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateCrypt {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('encrypter');
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