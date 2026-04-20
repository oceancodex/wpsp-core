<?php

namespace WPSPCORE\App\WordPress\AdminBarMenus;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

abstract class BaseAdminBarMenu extends BaseInstances {

	use ObjectToArrayTrait;

	public $id                = null;
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
		$this->overrideId($this->extraParams['full_path']);
	}

	/*
	 *
	 */

	public function init($id = null) {
		if ($this->id) {
			add_action('admin_bar_menu', function($wp_admin_bar) {
				$wp_admin_bar->add_node([
					'id'     => $this->id,
					'title'  => $this->title ?? $this->id,
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

	public function overrideId($id = null) {
		if ($id && !$this->id) {
			$this->id = $id;
		}
	}

}