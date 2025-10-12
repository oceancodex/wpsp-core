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

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv, $extraParams);
		$this->callback_function = $extraParams['callback_function'];
		$this->custom_properties = $extraParams['custom_properties'];
		$this->overrideLocation($extraParams['location']);
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