<?php

namespace WPSPCORE\Routes\Filters;

use WPSPCORE\Traits\HookRunnerTrait;

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