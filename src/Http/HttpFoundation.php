<?php

namespace WPSPCORE\Http;

use WPSPCORE\HttpFoundation\Request;

abstract class HttpFoundation {

	/** @var Request|null */
	public $request = null;

	public function __construct() {
		if (!$this->request) {
			$this->request = Request::createFromGlobals();
		}
	}

	/*
	 *
	 */

	public function wantsJson() {
		return $this->request->headers->get('Accept') === 'application/json';
	}

}