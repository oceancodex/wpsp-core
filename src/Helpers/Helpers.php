<?php

use Illuminate\Container\Container;

if (!function_exists('app')) {
	function app($abstract = null, $parameters = []) {
		if (is_null($abstract)) {
			return Container::getInstance();
		}

		return Container::getInstance()->make($abstract, $parameters);
	}
}
else {
	function wpspcore_app($abstract = null, $parameters = []) {
		if (is_null($abstract)) {
			return Container::getInstance();
		}

		return Container::getInstance()->make($abstract, $parameters);
	}
}