<?php

namespace OCBPCORE\Base;

use OCBPCORE\Objects\Http\HttpFoundation;

abstract class BaseController extends HttpFoundation {

	public function __construct() {
		parent::__construct();
	}

}