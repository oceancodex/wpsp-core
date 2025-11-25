<?php

namespace WPSPCORE\Traits;

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