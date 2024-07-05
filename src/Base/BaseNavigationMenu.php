<?php

namespace WPSPCORE\Base;

use WPSPCORE\Data\NavigationMenuData;
use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BaseNavigationMenu extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public ?string                   $location    = null;
	public ?string                   $description = null;
	public ?NavigationMenuData       $args        = null;
	public static BaseNavigationMenu $instance;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $location = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->overrideLocation($location);
		$this->prepareArguments();
		$this->customProperties();
		$this->maybePrepareArgumentsAgain($location);
		self::$instance = $this;
	}

	/*
	 *
	 */

	public function init($location = null): void {
		if ($this->location) {
			register_nav_menu($this->location, $this->description);
		}
	}

	/*
	 *
	 */

	public static function get(): false|string|null {
		self::instance()->args->echo = false;
		return wp_nav_menu(self::instance()->args);
	}

	public static function display(): void {
		wp_nav_menu(self::instance()->args);
	}

	public static function instance(): static {
		return self::$instance;
	}

	/*
	 *
	 */

	public function overrideLocation($location = null): void {
		if ($location && !$this->location) {
			$this->location = $location;
		}
	}

	public function prepareArguments(): void {
		$this->args = new NavigationMenuData($this);
		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
//				unset($this->args->{$key});
			}
		}
		unset($this->args->location);
		unset($this->args->description);
	}

	public function maybePrepareArgumentsAgain($location = null): void {
		if ($location !== $this->location) {
			$this->prepareArguments();
		}
	}

	/*
	 *
	 */

	abstract public function customProperties();

}