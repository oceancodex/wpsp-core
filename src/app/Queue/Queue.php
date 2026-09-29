<?php

namespace WPSPCORE\App\Queue;

use Illuminate\Queue\QueueManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Queue
 */
abstract class Queue extends BaseInstances {

	private ?QueueManager $facade;

	/*
	 *
	 */

	public function getFacade(): ?QueueManager {
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