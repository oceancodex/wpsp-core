<?php

namespace WPSPCORE\App\Broadcast;

use Illuminate\Broadcasting\BroadcastManager as IlluminateBroadcast;
use Illuminate\Contracts\Broadcasting\Factory as IlluminateBroadcastFactory;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Broadcast
 */
abstract class Broadcast extends BaseInstances {

	private ?IlluminateBroadcast $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateBroadcast {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(IlluminateBroadcastFactory::class);
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