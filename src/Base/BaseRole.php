<?php

namespace WPSPCORE\Base;

use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BaseRole extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public $role              = null;
	public $callback_function = null;
	public $custom_properties = null;

	public $display_name      = null;
	public $capabilities      = [];

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $role = null, $callback_function = null, $custom_properties = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->callback_function = $callback_function;
		$this->custom_properties = $custom_properties;
		$this->overrideRole($role);
		$this->customProperties();
	}

	/*
	 *
	 */

	public function init($role = null) {
		$role = $this->role ?? $role;
		if ($role) {
			$role = add_role(
				$role,
				$this->display_name ?? $this->role
			);
			if ($role) {
				foreach ($this->capabilities as $capability) {
					$role->add_cap($capability);
				}
				$role->add_cap('_role_bookmark_' . $this->funcs->_getAppShortName());
			}
		}
	}

	/*
	 *
	 */

	protected function overrideRole($role = null) {
		if ($role && !$this->role) {
			$this->role = $role;
		}
	}

	/*
	 *
	 */

	abstract public function customProperties();

}