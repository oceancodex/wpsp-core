<?php

namespace WPSPCORE\App\Context;

use Illuminate\Log\Context\Repository;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Log\Context\Repository
 * @mixin \Illuminate\Support\Facades\Context
 */
abstract class Context extends BaseInstances {

	private ?Repository $cloud;

	/*
	 *
	 */

	public function getContext(): ?Repository {
		return $this->cloud;
	}

	public function setContext() {
		$this->cloud = $this->funcs->_getApplication(Repository::class);
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

		return $instance->getContext()?->$method(...$arguments);
	}

}