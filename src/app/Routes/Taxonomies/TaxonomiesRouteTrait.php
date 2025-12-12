<?php

namespace WPSPCORE\App\Routes\Taxonomies;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait TaxonomiesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->taxonomies();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function taxonomies();

}