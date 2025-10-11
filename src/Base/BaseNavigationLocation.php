<?php

namespace WPSPCORE\Base;

use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BaseNavigationLocation extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public $location          = null;
	public $description       = null;
	public $callback_function = null;
	public $custom_properties = null;

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

	public function init($location = null) {
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

	public function overrideLocation($location = null) {
		if ($location && !$this->location) {
			$this->location = $location;
		}
	}

	/*
	 *
	 */

	abstract public function customProperties();

}