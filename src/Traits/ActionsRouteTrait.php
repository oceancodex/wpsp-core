<?php

namespace WPSPCORE\Traits;

trait ActionsRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->actions();
	}

	/*
     *
     */

	public function actions() {}

}