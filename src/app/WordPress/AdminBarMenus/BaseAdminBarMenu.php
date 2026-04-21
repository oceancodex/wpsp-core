<?php

namespace WPSPCORE\App\WordPress\AdminBarMenus;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

abstract class BaseAdminBarMenu extends BaseInstances {

	use ObjectToArrayTrait;

	public $name              = null;
	public $title             = null;
	public $href              = null;
	public $parent            = null;
	public $meta              = [];
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideName($this->extraParams['full_path']);
	}

	/*
	 *
	 */

	public function init($name = null) {
		if ($this->name) {
			add_action('admin_bar_menu', function($wp_admin_bar) {
				$wp_admin_bar->add_node([
					'id'     => $this->name,
					'title'  => $this->title ?? $this->name,
					'href'   => $this->href,
					'meta'   => $this->meta,
					'parent' => $this->parent,
				]);
			}, 999);
		}
	}


	/*
	 *
	 */

	public function overrideName($name = null) {
		if ($name && !$this->name) {
			$this->name = $name;
		}
	}

}