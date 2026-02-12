<?php

namespace WPSPCORE\App\Routes\FrontPages;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait FrontPagesRouteTrait {

	use HookRunnerTrait;

	public $funcs;
	public $mainPath;
	public $rootNamespace;
	public $prefixEnv;

	/*
	 *
	 */

	public function register() {
		$this->front_pages();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function front_pages();

}