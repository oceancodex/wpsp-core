<?php

namespace WPSPCORE\Routes\Actions;

use WPSPCORE\Traits\HookRunnerTrait;

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