<?php

namespace WPSPCORE\Base;

use WPSPCORE\Data\NavigationMenuData;
use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BaseNavigationMenu extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public              $args     = null;
	public static ?self $instance = null;

	/*
	 *
	 */

	protected function afterInstanceConstruct(): void {
		$this->prepareArguments();
		$this->customProperties();
	}

	/*
	 *
	 */

	public static function render() {
		self::instance()->args->echo = false;
		return wp_nav_menu(self::instance()->args->toArray());
	}

	protected static function instance(): ?self {
		if (!self::$instance || !self::$instance instanceof static) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	/*
	 *
	 */

	protected function prepareArguments(): void {
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