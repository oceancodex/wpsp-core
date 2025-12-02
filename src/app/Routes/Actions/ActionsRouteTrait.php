<?php

namespace WPSPCORE\App\Routes\Actions;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait ActionsRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->actions();
	}

	/*
     *
     */

	abstract public function actions();

}