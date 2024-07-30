<?php

namespace WPSPCORE\Http;

use WPSPCORE\HttpFoundation\Request;

abstract class HttpFoundation {

	public ?Request $request = null;

	public function __construct() {
		if (!$this->request) {
			$this->request = Request::createFromGlobals();
		}
	}

}