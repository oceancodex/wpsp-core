<?php

namespace WPSPCORE\Base;

use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BaseNavigationLocation extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public $location          = null;
	public $description       = null;
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideLocation($this->extraParams['location']);
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