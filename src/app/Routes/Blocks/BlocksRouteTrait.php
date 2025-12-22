<?php
namespace WPSPCORE\App\Routes\Blocks;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait BlocksRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->blocks();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function blocks();

}