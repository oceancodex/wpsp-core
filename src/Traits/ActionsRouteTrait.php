<?php

namespace WPSPCORE\Traits;

trait ActionsRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
		$this->actions();
	}

	/*
     *
     */

	public function actions() {}

}