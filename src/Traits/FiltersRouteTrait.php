<?php

namespace WPSPCORE\Traits;

trait FiltersRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
		$this->filters();
	}

	/*
     *
     */

	public function filters() {}

}