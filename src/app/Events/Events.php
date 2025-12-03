<?php

namespace WPSPCORE\App\Events;

use Illuminate\Events\Dispatcher as EventsDispatcher;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Event
 */
abstract class Events extends BaseInstances {

	private EventsDispatcher $events;

	/*
	 *
	 */

	public function getEvents(): EventsDispatcher {
		return $this->events;
	}

	public function setEvents(): void {
		$this->events = $this->funcs->getApplication('events');
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		$instance = static::instance();

		$underlineMethod = '_' . $method;
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		return $instance->getEvents()->$method(...$arguments);
	}

}