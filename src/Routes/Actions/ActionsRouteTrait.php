<?php

namespace WPSPCORE\Routes\Actions;

use WPSPCORE\Traits\HookRunnerTrait;
use WPSPCORE\Traits\RouteTrait;

trait ActionsRouteTrait {

	use HookRunnerTrait, RouteTrait;

	public function init() {
		$this->actions();
	}

	/*
     *
     */

	public function actions() {}

}