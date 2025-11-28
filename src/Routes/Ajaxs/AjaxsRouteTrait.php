<?php

namespace WPSPCORE\Routes\Ajaxs;

use WPSPCORE\Traits\HookRunnerTrait;

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