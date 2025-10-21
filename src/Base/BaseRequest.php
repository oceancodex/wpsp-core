<?php
namespace WPSPCORE\Base;

class BaseRequest {
	public static function createFromGlobals() {
		if (class_exists('\WPSPCORE\Validation\RequestWithValidation')) {
			return \WPSPCORE\Validation\RequestWithValidation::createFromGlobals();
		} else {
			return \Symfony\Component\HttpFoundation\Request::createFromGlobals();
		}
	}
}