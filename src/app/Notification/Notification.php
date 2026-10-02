<?php

namespace WPSPCORE\App\Notification;

use Illuminate\Notifications\ChannelManager as IlluminateNotification;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Notification
 */
abstract class Notification extends BaseInstances {

	private ?IlluminateNotification $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateNotification {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(IlluminateNotification::class);
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