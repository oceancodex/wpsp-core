<?php

namespace WPSPCORE\Base;

use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BaseRole extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public mixed $role              = null;
	public mixed $callback_function = null;
	public mixed $custom_properties = null;

	public mixed $display_name      = null;
	public mixed $capabilities      = [];

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

	public function init($role = null): void {
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

	protected function overrideRole($role = null): void {
		if ($role && !$this->role) {
			$this->role = $role;
		}
	}

	/*
	 *
	 */

	abstract public function customProperties();

}