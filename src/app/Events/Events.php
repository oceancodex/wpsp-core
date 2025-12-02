<?php

namespace WPSPCORE\App\Events;

use Illuminate\Events\Dispatcher as EventsDispatcher;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Event
 */
abstract class Events extends BaseInstances {

	private EventsDispatcher $events;

	public function getEvents(): EventsDispatcher {
		return $this->events;
	}

	public function setEvents(): void {
		$this->events = $this->funcs->getApplication('events');
	}

	/*
	 *
	 */

	public function __call($name, $arguments) {
		if (method_exists(static::instance(), $name)) {
			return static::instance()->$name(...$arguments);
		}
		else {
			return static::instance()->getEvents()->$name(...$arguments);
		}
	}

	public static function __callStatic($name, $arguments) {
		if (method_exists(static::instance(), $name)) {
			return static::instance()->$name(...$arguments);
		}
		else {
			return static::instance()->getEvents()->$name(...$arguments);
		}
	}

}