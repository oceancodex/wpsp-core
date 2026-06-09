<?php

namespace WPSPCORE\App\WordPress\NavigationMenus\Locations;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

abstract class BaseNavigationLocation extends BaseInstances {

	use ObjectToArrayTrait;

	public $location          = null;
	public $description       = null;

	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideLocation($this->extraParams['full_path']);
	}


	/*
	 *
	 */

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overrideLocation($location = null) {
		if ($location && !$this->location) {
			$this->location = $location;
		}
	}

	/*
	 *
	 */

	public function init($location = null) {
		if ($this->location) {
			register_nav_menu($this->location, $this->description);
		}
	}

}