<?php

namespace WPSPCORE\App\WordPress\WPRoles;

use WPSPCORE\BaseInstances;

class WPRoles extends BaseInstances {

	/**
	 * Removes all roles that possess a specified capability, except for the "administrator" role.
	 *
	 * @param string $capability The capability to check for in roles.
	 *
	 * @return void
	 */
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

	/**
	 * Removes all custom roles associated with a specific application by using a dynamically generated capability string.
	 *
	 * @return void
	 */
	public function removeAllCustomRoles() {
		$this->removeRolesByCapability('_role_bookmark_' . $this->funcs->_getAppShortName());
	}

}