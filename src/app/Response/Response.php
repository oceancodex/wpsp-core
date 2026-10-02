<?php

namespace WPSPCORE\App\Response;

use \Illuminate\Routing\ResponseFactory;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Response
 */
abstract class Response extends BaseInstances {

	private ?ResponseFactory $facade;

	/*
	 *
	 */

	public function getFacade(): ?ResponseFactory {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('response');
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