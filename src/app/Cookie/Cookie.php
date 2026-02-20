<?php

namespace WPSPCORE\App\Cookie;

use Illuminate\Cookie\CookieJar;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Cookie\CookieJar
 * @mixin \Illuminate\Support\Facades\Cookie
 */
abstract class Cookie extends BaseInstances {

	private CookieJar $cookie;

	/*
	 *
	 */

	public function getCookie(): CookieJar {
		return $this->cookie;
	}

	public function setCookie() {
		$this->cookie = $this->funcs->getApplication('cookie');
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

		return $instance->getCookie()->$method(...$arguments);
	}

}