<?php

namespace WPSPCORE\App\Lang;

use Illuminate\Translation\Translator;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Translation\Translator
 * @mixin \Illuminate\Support\Facades\Lang
 */
abstract class Lang extends BaseInstances {

	private ?Translator $facade;

	/*
	 *
	 */

	public function getFacade(): ?Translator {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('translator');
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