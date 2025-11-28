<?php

namespace WPSPCORE\Routes\PostTypes;

use WPSPCORE\Traits\HookRunnerTrait;

trait PostTypesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->post_types();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function post_types();

}