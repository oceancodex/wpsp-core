<?php

namespace WPSPCORE\App\Auth;

use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Auth\Passwords\PasswordBrokerManager
 * @mixin \Illuminate\Support\Facades\Password
 */
abstract class Password extends BaseInstances {

	private $password;

	/*
	 *
	 */

	public function getPassword() {
		return $this->password;
	}

	public function setPassword() {
		$this->password = $this->funcs->getApplication('auth.password');
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		$instance = static::instance();

		$underlineMethod = '_' . $method;
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		return $instance->getPassword()->$method(...$arguments);
	}

}