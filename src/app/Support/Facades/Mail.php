<?php

namespace WPSPCORE\App\Support\Facades;

use Illuminate\Mail\MailManager as IlluminateMail;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Mail
 */
abstract class Mail extends BaseInstances {

	private ?IlluminateMail $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateMail {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('mail.manager');
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