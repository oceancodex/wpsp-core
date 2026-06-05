<?php

namespace WPSPCORE\App\WordPress\NavigationMenus\Menus;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

/**
 * @method static static instance
 */
abstract class BaseNavigationMenu extends BaseInstances {

	use ObjectToArrayTrait;

	public  $args 				  = null;

	public  $menu                 = null;
	public  $menu_class           = '';
	public  $menu_id              = '';            // The "id" attribute of the <ul> element.
	public  $container            = '';
	public  $container_class      = '';
	public  $container_id         = '';
	public  $container_aria_label = '';
	public  $fallback_cb          = false;         // If the menu doesn’t exist, a callback function will fire.
	public  $before               = '';
	public  $after                = '';
	public  $link_before          = '';
	public  $link_after           = '';
	public  $echo                 = true;
	public  $depth                = 0;
	public  $walker               = null;
	public  $theme_location       = '';
	public  $items_wrap           = '';
	public  $item_spacing         = '';            // 'preserve' or 'discard'

	/*
	 *
	 */

	public function afterConstruct() {
		$this->args = new NavigationMenuData($this);
	}

	/**
	 * Ở class con (ví dụ: Menu1).\
	 * Sau khi custom properties thì cần chạy prepareArguments()\
	 * trong hàm afterBaseInstanceConstruct() vì hàm này chạy sau customProperties().
	 */
	public function afterBaseInstanceConstruct() {
		$this->prepareArguments();
	}

	/*
	 *
	 */

	public function renderNavMenu() {
		$args = $this->args->toArray();
		if (wp_get_nav_menu_object($args['menu'])) {
			return wp_nav_menu($args);
		}
		return false;
	}

	/*
	 *
	 */

	protected function prepareArguments() {
		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
			}
		}

		// Unset "items_wrap" if it's empty.
//		if (isset($this->items_wrap) && $this->items_wrap) {
//			$this->args->items_wrap = $this->items_wrap;
//		}
//		else {
//			unset($this->args->items_wrap);
//		}

		// Default menu name.
		$this->args->menu = $this->args->menu ?: strtolower(basename(static::class));
	}

}