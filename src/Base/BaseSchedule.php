<?php

namespace WPSPCORE\Base;

use WPSPCORE\Objects\Http\HttpFoundation;

abstract class BaseSchedule extends HttpFoundation {

	public function __construct() {
		parent::__construct();
	}

	/*
	 *
	 */

	abstract public function init();

}