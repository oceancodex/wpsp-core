<?php

namespace WPSPCORE\App\WordPress\AdminBarMenus;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

abstract class BaseAdminBarMenu extends BaseInstances {

	use ObjectToArrayTrait;

	public $id                = null;
	public $title             = null;
	public $href              = null;
	public $parent            = '';
	public $meta              = [];

	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideId($this->extraParams['full_path'] ?? null);
	}

	/*
	 *
	 */

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overrideId($id = null) {
		if ($id && !$this->id) {
			$this->id = $id;
		}
	}

	/*
	 *
	 */

	public function init($id = null) {
		$id = $this->id ?? $id;

		if ($id) {
			add_action('admin_bar_menu', function($wp_admin_bar) use ($id) {
				$wp_admin_bar->add_node([
					'id'     => $id,
					'title'  => $this->title ?? $this->id,
					'href'   => $this->href,
					'meta'   => $this->meta,
					'parent' => $this->parent,
				]);
			}, $this->extraParams['priority'] ?? 999);
		}
	}

}