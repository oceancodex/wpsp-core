<?php

namespace WPSPCORE\App\Session;

use Illuminate\Cookie\CookieJar;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Session\SessionManager
 * @mixin \Illuminate\Support\Facades\Session
 */
abstract class Session extends BaseInstances {

	private ?\Illuminate\Session\SessionManager $session;

	/*
	 *
	 */

	public function getSession(): ?\Illuminate\Session\SessionManager {
		return $this->session;
	}

	public function setSession() {
		$this->session = $this->funcs->_getApplication('session');
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

		return $instance->getSession()?->$method(...$arguments);
	}

}