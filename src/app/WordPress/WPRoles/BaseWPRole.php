<?php

namespace WPSPCORE\App\WordPress\WPRoles;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

abstract class BaseWPRole extends BaseInstances {

	use ObjectToArrayTrait;

	public $role              = null;
	public $callback_function = null;
	public $display_name      = null;
	public $capabilities      = [];

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideRole($this->extraParams['full_path']);
	}

	/*
	 *
	 */

	public function init($role = null) {
		$role = $this->role ?? $role;
		if ($role) {
			$exitsRole = get_role($role);

			if ($exitsRole) {
				$role = $exitsRole;
			}
			else {
				$role = add_role(
					$role,
					$this->display_name ?? $this->role
				);
			}

			if ($role) {
				foreach ($this->capabilities as $capability) {
					if (!$role->has_cap($capability)) {
						$role->add_cap($capability);
					}
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

}