<?php

namespace WPSPCORE\App\Auth;

use Illuminate\Contracts\Auth\PasswordBroker;
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

	const ResetLinkSent   = PasswordBroker::RESET_LINK_SENT;
	const PasswordReset   = PasswordBroker::PASSWORD_RESET;
	const InvalidUser     = PasswordBroker::INVALID_USER;
	const InvalidToken    = PasswordBroker::INVALID_TOKEN;
	const ResetThrottled  = PasswordBroker::RESET_THROTTLED;
	const RESET_LINK_SENT = PasswordBroker::RESET_LINK_SENT;
	const PASSWORD_RESET  = PasswordBroker::PASSWORD_RESET;
	const INVALID_USER    = PasswordBroker::INVALID_USER;
	const INVALID_TOKEN   = PasswordBroker::INVALID_TOKEN;
	const RESET_THROTTLED = PasswordBroker::RESET_THROTTLED;

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