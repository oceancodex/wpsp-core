<?php

namespace WPSPCORE\App\Routes\Filters;

trait FiltersRouteTrait {

	public function register(): void {
		$this->filters();
		$this->wp_filters();
	}

	/*
	 *
	 */

	abstract public function filters();

	abstract public function wp_filters();

}