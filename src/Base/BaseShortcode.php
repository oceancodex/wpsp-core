<?php

namespace OCBPCORE\Base;

use OCBPCORE\Objects\Http\HttpFoundation;

abstract class BaseShortcode extends HttpFoundation {

	public function __construct() {
		parent::__construct();
	}

	/*
	 *
	 */

	abstract public function init($atts, $content, $tag);

}