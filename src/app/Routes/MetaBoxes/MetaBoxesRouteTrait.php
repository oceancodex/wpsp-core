<?php

namespace WPSPCORE\App\Routes\MetaBoxes;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait MetaBoxesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->meta_boxes();
		$this->hooks();
	}

	/*
     *
     */

	public function meta_boxes() {}

}