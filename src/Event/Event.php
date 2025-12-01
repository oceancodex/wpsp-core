<?php

namespace WPSPCORE\Event;

use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Events\Dispatcher
 * @mixin \Illuminate\Support\Facades\Event
 */
abstract class Event extends BaseInstances {

	private $event;

	public function getEvent() {
		return $this->event;
	}

	public function setEvent(): void {
		$this->event = $this->funcs->getApplication('events');
	}

	/*
	 *
	 */

	public function __call($name, $arguments) {
		if (method_exists(static::instance(), $name)) {
			return static::instance()->$name(...$arguments);
		}
		else {
			return static::instance()->getEvent()->$name(...$arguments);
		}
	}

	public static function __callStatic($name, $arguments) {
		if (method_exists(static::instance(), $name)) {
			return static::instance()->$name(...$arguments);
		}
		else {
			return static::instance()->getEvent()->$name(...$arguments);
		}
	}

}