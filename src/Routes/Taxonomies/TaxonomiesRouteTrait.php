<?php

namespace WPSPCORE\Routes\Taxonomies;

use WPSPCORE\Traits\HookRunnerTrait;

trait TaxonomiesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->taxonomies();
		$this->hooks();
	}

	/*
     *
     */

	public function taxonomies() {}

}