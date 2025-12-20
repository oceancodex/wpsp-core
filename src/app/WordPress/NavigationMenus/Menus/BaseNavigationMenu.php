<?php

namespace WPSPCORE\App\WordPress\NavigationMenus\Menus;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

/**
 * @method static static instance
 */
abstract class BaseNavigationMenu extends BaseInstances {

	use ObjectToArrayTrait;

	public $args = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->prepareArguments();
	}

	/*
	 *
	 */

	public static function render() {
		$instance = static::instance();
		$instance->args->echo = false;
		$args = $instance->args->toArray();
		if (wp_get_nav_menu_object($args['menu'])) {
			return wp_nav_menu($args);
		}
		return false;
	}

	/*
	 *
	 */

	protected function prepareArguments() {
		$this->args = new NavigationMenuData($this);
		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
			}
		}

		// Unset "items_wrap" if it's empty.
		if (isset($this->items_wrap) && $this->items_wrap) {
			$this->args->items_wrap = $this->items_wrap;
		}
		else {
			unset($this->args->items_wrap);
		}

		// Default menu name.
		$this->args->menu = $this->args->menu ?: strtolower(basename(static::class));
	}

}