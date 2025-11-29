<?php

namespace WPSPCORE\Routes\TaxonomyColumns;

use WPSPCORE\Traits\HookRunnerTrait;

trait TaxonomyColumnsRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->taxonomy_columns();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function taxonomy_columns();

}