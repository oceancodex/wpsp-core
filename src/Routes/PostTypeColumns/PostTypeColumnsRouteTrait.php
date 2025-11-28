<?php

namespace WPSPCORE\Routes\PostTypeColumns;

use WPSPCORE\Traits\HookRunnerTrait;

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