<?php

namespace WPSPCORE\App\Cookie;

use Illuminate\Cookie\CookieJar;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Cookie
 */
abstract class Cookie extends BaseInstances {

	private ?CookieJar $facade;

	/*
	 *
	 */

	public function getFacade(): ?CookieJar {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('cookie');
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