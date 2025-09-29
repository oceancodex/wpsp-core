<?php

namespace WPSPCORE\Objects;

use WPSPCORE\Base\BaseInstances;

class WPRoles extends BaseInstances {

	public function removeRolesByCapability(string $capability): void {
		global $wp_roles;
		foreach ($wp_roles->roles as $role_name => $role_data) {
			if (isset($role_data['capabilities'][$capability]) && $role_data['capabilities'][$capability]) {
				if ($role_name !== 'administrator') {
					remove_role($role_name);
				}
			}
		}
	}

	public function removeAllCustomRoles(): void {
		$this->removeRolesByCapability('_role_bookmark_' . $this->funcs->_getAppShortName());
	}

}