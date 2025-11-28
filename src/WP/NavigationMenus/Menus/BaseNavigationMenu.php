<?php

namespace WPSPCORE\WP\NavigationMenus\Menus;

use WPSPCORE\BaseInstances;
use WPSPCORE\Traits\ObjectToArrayTrait;

abstract class BaseNavigationMenu extends BaseInstances {

	use ObjectToArrayTrait;

	public        $args     = null;
	public static $instance = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->prepareArguments();
		$this->customProperties();
	}

	/*
	 *
	 */

	public static function instance() {
		if (!self::$instance || !self::$instance instanceof static) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public static function render() {
		self::instance()->args->echo = false;
		$args                        = self::instance()->args->toArray();
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
	}

	/*
	 *
	 */

	abstract public function customProperties();

}