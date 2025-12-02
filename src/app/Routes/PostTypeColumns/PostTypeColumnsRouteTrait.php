<?php

namespace WPSPCORE\App\Routes\PostTypeColumns;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait PostTypeColumnsRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->post_type_columns();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function post_type_columns();

}