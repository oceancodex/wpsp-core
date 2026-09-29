<?php

namespace WPSPCORE\App\Context;

use Illuminate\Log\Context\Repository;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Context
 */
abstract class Context extends BaseInstances {

	private ?Repository $facade;

	/*
	 *
	 */

	public function getFacade(): ?Repository {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(Repository::class);
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