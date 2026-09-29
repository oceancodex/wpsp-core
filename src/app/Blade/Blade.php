<?php

namespace WPSPCORE\App\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Blade
 */
abstract class Blade extends BaseInstances {

	private ?BladeCompiler $facade;

	/*
	 *
	 */

	public function getFacade(): ?BladeCompiler {
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