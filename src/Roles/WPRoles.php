<?php

namespace WPSPCORE\Roles;

use WPSPCORE\Base\BaseInstances;

class WPRoles extends BaseInstances {

	public function removeRolesByCapability($capability) {
		global $wp_roles;
		foreach ($wp_roles->roles as $role_name => $role_data) {
			if (isset($role_data['capabilities'][$capability]) && $role_data['capabilities'][$capability]) {
				if ($role_name !== 'administrator') {
					remove_role($role_name);
				}
			}
		}
	}

	public function removeAllCustomRoles() {
		$this->removeRolesByCapability('_role_bookmark_' . $this->funcs->_getAppShortName());
	}

}