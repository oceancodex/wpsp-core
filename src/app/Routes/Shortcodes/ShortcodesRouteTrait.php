<?php

namespace WPSPCORE\App\Routes\Shortcodes;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait ShortcodesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->shortcodes();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function shortcodes();

}