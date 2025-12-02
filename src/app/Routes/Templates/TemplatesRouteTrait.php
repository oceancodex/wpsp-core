<?php

namespace WPSPCORE\App\Routes\Templates;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait TemplatesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->templates();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function templates();

	/*
	 *
	 */

	public function template($name, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10, $argsNumber = 1) {
	}

}