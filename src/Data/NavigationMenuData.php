<?php

namespace WPSPCORE\Data;

use WPSPCORE\Base\BaseData;
use WPSPCORE\Base\BaseNavigationMenu;
use WPSPCORE\Base\BasePostType;

class NavigationMenuData extends BaseData {

	public mixed $location;
	public mixed $description;

	// Args.
	public mixed $menu;
	public mixed $menu_class;
	public mixed $menu_id;
	public mixed $container;
	public mixed $container_class;
	public mixed $container_id;
	public mixed $container_aria_label;
	public mixed $fallback_cb;
	public mixed $before;
	public mixed $after;
	public mixed $link_before;
	public mixed $link_after;
	public mixed $echo;
	public mixed $depth;
	public mixed $walker;
	public mixed $theme_location;
	public mixed $items_wrap;
	public mixed $item_spacing;

	// Custom properties.
	public mixed $navigationMenuInstance;

	public function __construct(BaseNavigationMenu $navigationMenuInstance = null) {
		$this->navigationMenuInstance = $navigationMenuInstance;
		$this->prepareCustomVariables();
		$this->prepareArgs();
	}

	public function prepareArgs(): void {
//		$this->menu                 = '';
		$this->menu_class           = 'menu';
//		$this->menu_id              = '';
		$this->container            = 'div';
//		$this->container_class      = '';
//		$this->container_id         = '';
//		$this->container_aria_label = '';
		$this->fallback_cb          = false;
//		$this->before               = '';
//		$this->after                = '';
//		$this->link_before          = '';
//		$this->link_after           = '';
		$this->echo                 = true;
		$this->depth                = 0;
//		$this->walker               = '';
//		$this->theme_location       = '';
//		$this->items_wrap           = '';
//		$this->item_spacing         = '';
	}

	public function prepareCustomVariables(): void {
		unset($this->navigationMenuInstance);
	}

}