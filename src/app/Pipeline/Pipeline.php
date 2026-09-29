<?php

namespace WPSPCORE\App\Pipeline;

use \Illuminate\Pipeline\Pipeline as PipelineCore;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Pipeline
 */
abstract class Pipeline extends BaseInstances {

	private ?PipelineCore $facade;

	/*
	 *
	 */

	public function getFacade(): ?PipelineCore {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('pipeline');
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