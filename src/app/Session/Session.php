<?php

namespace WPSPCORE\App\Session;

use Illuminate\Cookie\CookieJar;
use Illuminate\Session\SessionManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Session
 */
abstract class Session extends BaseInstances {

	private ?SessionManager $facade;

	/*
	 *
	 */

	public function getFacade(): ?SessionManager {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('session');
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