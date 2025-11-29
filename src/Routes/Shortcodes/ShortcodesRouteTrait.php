<?php

namespace WPSPCORE\Routes\Shortcodes;

use WPSPCORE\Traits\HookRunnerTrait;

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