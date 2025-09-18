<?php

namespace WPSPCORE\Base;

use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BaseNavigationLocation extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public ?string $location          = null;
	public ?string $description       = null;
	public mixed   $callback_function = null;
	public mixed   $custom_properties = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $location = null, $callback_function = null, $custom_properties = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->callback_function = $callback_function;
		$this->custom_properties = $custom_properties;
		$this->overrideLocation($location);
		$this->customProperties();
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


	/*
	 *
	 */

	public function overrideLocation($location = null): void {
		if ($location && !$this->location) {
			$this->location = $location;
		}
	}

	/*
	 *
	 */

	abstract public function customProperties();

}