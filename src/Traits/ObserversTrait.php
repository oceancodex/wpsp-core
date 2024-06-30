<?php

namespace WPSPCORE\Traits;

use Illuminate\Support\Facades\Gate;

trait ObserversTrait {

	protected static function boot(): void {
		parent::boot();
		static::setEventDispatcher(new \Illuminate\Events\Dispatcher());
		foreach (self::$observers as $observer) {
			static::observe(new $observer());
		}
	}

}