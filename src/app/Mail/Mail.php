<?php

namespace WPSPCORE\App\Mail;

use Illuminate\Mail\Mailer;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Mail
 */
abstract class Mail extends BaseInstances {

	private Mailer $mail;

	/*
	 *
	 */

	public function getMail(): Mailer {
		return $this->mail;
	}

	public function setMail() {
		$this->mail = $this->funcs->getApplication('mailer');
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

		return $instance->getMail()->$method(...$arguments);
	}

}