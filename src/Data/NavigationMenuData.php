<?php

namespace WPSPCORE\Data;

use WPSPCORE\Base\BaseData;
use WPSPCORE\Base\BaseNavigationMenu;

class NavigationMenuData extends BaseData {

	public $location;
	public $description;

	// Args.
	public $menu;
	public $menu_class;
	public $menu_id;
	public $container;
	public $container_class;
	public $container_id;
	public $container_aria_label;
	public $fallback_cb;
	public $before;
	public $after;
	public $link_before;
	public $link_after;
	public $echo;
	public $depth;
	public $walker;
	public $theme_location;
	public $items_wrap;
	public $item_spacing;

	// Custom properties.
//	public $navigationMenuInstance;

	public function __construct(BaseNavigationMenu $navigationMenuInstance = null) {
//		$this->navigationMenuInstance = $navigationMenuInstance;
//		$this->prepareCustomVariables();
		$this->prepareArgs();
	}

	private function prepareArgs(): void {
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
		$this->echo                 = false;
		$this->depth                = 0;
//		$this->walker               = '';
//		$this->theme_location       = '';
//		$this->items_wrap           = '';
//		$this->item_spacing         = '';
	}

//	private function prepareCustomVariables(): void {
//		unset($this->navigationMenuInstance);
//	}

}