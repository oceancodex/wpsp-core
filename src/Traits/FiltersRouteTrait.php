<?php

namespace WPSPCORE\Traits;

trait FiltersRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->filters();
	}

	/*
     *
     */

	public function filters() {}

}