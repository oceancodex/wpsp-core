<?php

namespace WPSPCORE\App\Hash;

use Illuminate\Hashing\HashManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Hashing\HashManager
 * @mixin \Illuminate\Support\Facades\Hash
 */
abstract class Hash extends BaseInstances {

	private ?HashManager $hash;

	/*
	 *
	 */

	public function getHash(): ?HashManager {
		return $this->hash;
	}

	public function setHash() {
		$this->hash = $this->funcs->_getApplication('hash');
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

		return $instance->getHash()?->$method(...$arguments);
	}

}