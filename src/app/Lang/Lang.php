<?php

namespace WPSPCORE\App\Lang;

use Illuminate\Translation\Translator;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Translation\Translator
 * @mixin \Illuminate\Support\Facades\Lang
 */
abstract class Lang extends BaseInstances {

	private Translator $lang;

	/*
	 *
	 */

	public function getLang(): Translator {
//		return $this->funcs->getApplication('translator');
		return $this->lang;
	}

	public function setLang() {
		$this->lang = $this->funcs->getApplication('translator');
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

		return $instance->getLang()->$method(...$arguments);
	}

}