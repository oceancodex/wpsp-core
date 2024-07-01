<?php

namespace WPSPCORE\Traits;

use Symfony\Component\HttpFoundation\Request;

trait HttpRequestTrait {

	public static ?Request $request = null;

	/*
	 * 
	 */

	public static function request(): ?Request {
		if (!self::$request) {
			self::$request = Request::createFromGlobals();
		}
		return self::$request;
	}

}