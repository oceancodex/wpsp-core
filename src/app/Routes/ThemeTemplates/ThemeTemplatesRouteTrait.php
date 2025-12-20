<?php

namespace WPSPCORE\App\Routes\ThemeTemplates;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait ThemeTemplatesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->theme_templates();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function theme_templates();

}