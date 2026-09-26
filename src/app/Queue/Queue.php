<?php

namespace WPSPCORE\App\Queue;

use Illuminate\Queue\QueueManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin QueueManager
 * @mixin \Illuminate\Support\Facades\Queue
 */
abstract class Queue extends BaseInstances {

	private ?QueueManager $queue;

	/*
	 *
	 */

	public function getQueue(): ?QueueManager {
		return $this->queue;
	}

	public function setQueue() {
		$this->queue = $this->funcs->_getApplication('queue');
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

		return $instance->getQueue()?->$method(...$arguments);
	}

}