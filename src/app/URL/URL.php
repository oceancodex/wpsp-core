<?php

namespace WPSPCORE\App\URL;

use Illuminate\Routing\UrlGenerator as IlluminateUrl;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\URL
 */
abstract class URL extends BaseInstances {

	private ?IlluminateUrl $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateUrl {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('url');
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