<?php

namespace WPSPCORE\App\Broadcast;

use Illuminate\Broadcasting\BroadcastManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Contracts\Broadcasting\Factory
 * @mixin \Illuminate\Broadcasting\BroadcastManager
 * @mixin \Illuminate\Support\Facades\Broadcast
 */
abstract class Broadcast extends BaseInstances {

	private ?BroadcastManager $broadcast;

	/*
	 *
	 */

	public function getBroadcast(): ?BroadcastManager {
		return $this->broadcast;
	}

	public function setBroadcast() {
		$this->broadcast = $this->funcs->_getApplication(\Illuminate\Contracts\Broadcasting\Factory::class);
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

		return $instance->getBroadcast()?->$method(...$arguments);
	}

}