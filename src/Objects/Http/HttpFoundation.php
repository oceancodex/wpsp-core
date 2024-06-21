<?php

namespace OCBPCORE\Objects\Http;

use Symfony\Component\HttpFoundation\Request;

abstract class HttpFoundation {

	/**
	 * @var Request $request
	 */
	public static ?Request $request = null;

	public function __construct() {
		if (!self::$request) {
			self::$request = Request::createFromGlobals();
		}
	}

}