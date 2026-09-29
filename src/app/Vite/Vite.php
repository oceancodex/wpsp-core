<?php

namespace WPSPCORE\App\Vite;

use Illuminate\Foundation\Vite as ViteCore;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Vite
 */
abstract class Vite extends BaseInstances {

	private ?ViteCore $facade;

	/*
	 *
	 */

	public function getFacade(): ?ViteCore {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('validator');
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