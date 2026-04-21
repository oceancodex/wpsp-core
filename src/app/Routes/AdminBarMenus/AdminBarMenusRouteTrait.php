<?php

namespace WPSPCORE\App\Routes\AdminBarMenus;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait AdminBarMenusRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->admin_bar_menus();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function admin_bar_menus();

}