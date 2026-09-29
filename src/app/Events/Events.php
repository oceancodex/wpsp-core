<?php

namespace WPSPCORE\App\Events;

use Illuminate\Events\Dispatcher as EventsDispatcher;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Event
 */
abstract class Events extends BaseInstances {

	private ?EventsDispatcher $facade;

	/*
	 *
	 */

	public function getFacade(): ?EventsDispatcher {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('events');
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