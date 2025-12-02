<?php

namespace WPSPCORE\App\Routes\AdminPages;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait AdminPagesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->admin_pages();
		$this->hooks();
	}

	abstract public function admin_pages();

}