<?php

namespace WPSPCORE\App\Routes\CommentColumns;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait CommentColumnsRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->comment_columns();
		$this->hooks();
	}

	/*
     *
     */

	abstract public function comment_columns();

}