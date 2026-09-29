<?php

namespace WPSPCORE\App\View;

use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\View
 */
abstract class View extends BaseInstances {

	private ?\Illuminate\View\Factory $facade;

	/*
	 *
	 */

	public function getFacade(): ?\Illuminate\View\Factory {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('view');
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		$instance = static::wpspInstance();

		$underlineMethod = '_' . $method;
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		return $instance->getFacade()?->$method(...$arguments);
	}

}