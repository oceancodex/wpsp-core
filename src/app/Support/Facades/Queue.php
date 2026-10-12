<?php

namespace WPSPCORE\App\Support\Facades;

use Illuminate\Queue\QueueManager as IlluminateQueue;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Queue
 */
abstract class Queue extends BaseInstances {

	private ?IlluminateQueue $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateQueue {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('queue');
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