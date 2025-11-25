<?php

namespace WPSPCORE\Routes;

use WPSPCORE\Traits\RouteTrait;

class AjaxsRoute extends \WPSPCORE\Base\BaseRouter {

	use RouteTrait;

	/*
	 *
	 */

	public static function __callStatic($name, $arguments) {
		$name = '_' . $name;
		return static::instance()->$name(...$arguments);
	}

	public function __call($name, $arguments) {
		$name = '_' . $name;
		return $this->$name(...$arguments);
	}

}