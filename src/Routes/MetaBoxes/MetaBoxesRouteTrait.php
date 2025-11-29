<?php

namespace WPSPCORE\Routes\MetaBoxes;

use WPSPCORE\Traits\HookRunnerTrait;

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