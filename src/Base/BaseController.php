<?php

namespace WPSPCORE\Base;

use WPSPCORE\Http\HttpFoundation;

abstract class BaseController extends HttpFoundation {

	public function __construct() {
		parent::__construct();
	}

}