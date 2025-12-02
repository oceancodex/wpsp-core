<?php

namespace WPSPCORE\App\Routes\Ajaxs;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait AjaxsRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->ajaxs();
		$this->hooks();
	}

	/*
	 *
	 */

	abstract public function ajaxs();

}