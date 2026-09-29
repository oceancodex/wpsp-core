<?php

namespace WPSPCORE\App\Mail;

use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Mail
 */
abstract class Mailer extends BaseInstances {

	private ?\Illuminate\Mail\Mailer $facade;

	/*
	 *
	 */

	public function getFacade(): ?\Illuminate\Mail\Mailer {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('mailer');
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