<?php

namespace WPSPCORE\Traits;

use Symfony\Component\HttpFoundation\Request;

trait HttpRequestTrait {

	/** @var Request|null */
	public static $request = null;

	/*
	 * 
	 */

	public static function request() {
		if (!self::$request) {
			self::$request = Request::createFromGlobals();
		}
		return self::$request;
	}

}