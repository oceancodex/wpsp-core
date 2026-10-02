<?php

namespace WPSPCORE\App\Mail;

use Illuminate\Mail\Mailer as IlluminateMailer;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Mail
 */
abstract class Mailer extends BaseInstances {

	private ?IlluminateMailer $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateMailer {
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