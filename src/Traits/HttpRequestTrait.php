<?php

namespace WPSPCORE\Traits;

use Symfony\Component\HttpFoundation\Request;

trait HttpRequestTrait {

	/**
	 * @var Request
	 */
	public static Request $request;
	
	/*
	 * 
	 */

	public function __construct() {
	}

}