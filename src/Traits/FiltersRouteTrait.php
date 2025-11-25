<?php

namespace WPSPCORE\Traits;

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