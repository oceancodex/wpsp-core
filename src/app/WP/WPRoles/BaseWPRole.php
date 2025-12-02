<?php

namespace WPSPCORE\App\WP\WPRoles;

use WPSPCORE\App\BaseInstances;
use WPSPCORE\App\Traits\ObjectToArrayTrait;

abstract class BaseWPRole extends BaseInstances {

	use ObjectToArrayTrait;

	public ?string $role              = null;
	public ?string $callback_function = null;
	public ?string $display_name      = null;
	public array   $capabilities      = [];

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideRole($this->extraParams['role']);
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