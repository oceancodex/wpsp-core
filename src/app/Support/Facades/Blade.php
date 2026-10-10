<?php

namespace WPSPCORE\App\Support\Facades;

use Illuminate\View\Compilers\BladeCompiler as IlluminateBlade;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Blade
 */
abstract class Blade extends BaseInstances {

	private ?IlluminateBlade $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateBlade {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('blade.compiler');
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