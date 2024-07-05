<?php

namespace WPSPCORE\Base;

use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BaseNavigationLocation extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public ?string $location    = null;
	public ?string $description = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $location = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
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