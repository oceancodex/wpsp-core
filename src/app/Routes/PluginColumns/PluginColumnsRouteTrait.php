<?php

namespace WPSPCORE\App\Routes\PluginColumns;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait PluginColumnsRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->plugin_columns();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function plugin_columns();

}