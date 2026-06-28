<?php

namespace WPSPCORE\App\Routes\UserColumns;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait UserColumnsRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->user_columns();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function user_columns();

}