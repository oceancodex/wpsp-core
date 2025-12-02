<?php

namespace WPSPCORE\App\Routes\Filters;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait FiltersRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->filters();
	}

	/*
     *
     */

	abstract public function filters();

}