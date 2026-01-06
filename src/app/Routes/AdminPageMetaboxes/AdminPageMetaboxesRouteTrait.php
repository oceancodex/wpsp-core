<?php

namespace WPSPCORE\App\Routes\AdminPageMetaboxes;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait AdminPageMetaboxesRouteTrait {

	use HookRunnerTrait;

	public function register() {
		$this->admin_page_metaboxes();
		$this->hooks();
	}

	/*
	 *
	 */

	abstract public function admin_page_metaboxes();

}