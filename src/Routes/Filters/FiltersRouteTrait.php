<?php

namespace WPSPCORE\Routes\Filters;

use WPSPCORE\Traits\HookRunnerTrait;
use WPSPCORE\Traits\RouteTrait;

trait FiltersRouteTrait {

	use HookRunnerTrait, RouteTrait;

	public function init() {
		$this->filters();
	}

	/*
     *
     */

	public function filters() {}

}