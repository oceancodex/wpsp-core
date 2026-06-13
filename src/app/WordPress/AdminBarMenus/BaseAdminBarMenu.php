<?php

namespace WPSPCORE\App\WordPress\AdminBarMenus;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

abstract class BaseAdminBarMenu extends BaseInstances {

	use ObjectToArrayTrait;

	public $name              = null;
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
		$this->overrideName($this->extraParams['full_path'] ?? null);
	}

	/*
	 *
	 */

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overrideName($name = null) {
		if ($name && !$this->name) {
			$this->name = $name;
		}
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
			}, $this->extraParams['priority'] ?? 999);
		}
	}

}