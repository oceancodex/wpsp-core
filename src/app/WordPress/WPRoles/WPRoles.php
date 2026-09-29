<?php

namespace WPSPCORE\App\WordPress\WPRoles;

use WPSPCORE\BaseInstances;
use BadMethodCallException;

/**
 * @method static void removeRolesByCapability(string $capability)
 * @method static void removeAllCustomRoles()
 */
abstract class WPRoles extends BaseInstances {

	protected mixed $facade = null;

	/**
	 * Lấy đối tượng Facade
	 */
	public function getFacade(): mixed {
		return $this->facade;
	}

	/**
	 * Thiết lập Facade
	 */
	public function setFacade(mixed $facade = null): void {
		$this->facade = $facade ?? $this->funcs->_getApplication(static::class);
	}

	/**
	 * Xóa tất cả vai trò có chứa capability được chỉ định (ngoại trừ administrator)
	 *
	 * @param string $capability Capability cần kiểm tra
	 *
	 * @return void
	 */
	public function _removeRolesByCapability(string $capability): void {
		// Tự động khởi tạo $wp_roles nếu chưa load
		$wpRolesObj = wp_roles();

		if (empty($wpRolesObj->roles) || !is_array($wpRolesObj->roles)) {
			return;
		}

		// Tách danh sách cần xóa để tránh làm hỏng con trỏ mảng khi gọi remove_role trong foreach
		$rolesToRemove = [];

		foreach ($wpRolesObj->roles as $roleName => $roleData) {
			if ($roleName === 'administrator') {
				continue;
			}

			if (!empty($roleData['capabilities'][$capability])) {
				$rolesToRemove[] = $roleName;
			}
		}

		foreach ($rolesToRemove as $roleName) {
			remove_role($roleName);
		}
	}

	/**
	 * Xóa các vai trò tùy chỉnh dựa theo Prefix App Short Name
	 *
	 * @return void
	 */
	public function _removeAllCustomRoles(): void {
		$cap = '_role_bookmark_' . $this->funcs->_getAppShortName();
		$this->_removeRolesByCapability($cap);
	}

	/**
	 * Proxy gọi method động trên object instance
	 */
	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	/**
	 * Proxy gọi static method động
	 */
	public static function __callStatic($method, $arguments) {
		$instance        = static::wpspInstance();
		$underlineMethod = '_' . $method;

		// 1. Kiểm tra method có tiền tố '_' trên instance
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		// 2. Kiểm tra method nguyên bản trên instance
		if (method_exists($instance, $method)) {
			return $instance->$method(...$arguments);
		}

		// 3. Chuyển tiếp tới Facade (nếu có)
		$facade = $instance->getFacade();
		if ($facade !== null && method_exists($facade, $method)) {
			return $facade->$method(...$arguments);
		}

		throw new BadMethodCallException(sprintf(
			'Method %s::%s() does not exist.',
			static::class,
			$method
		));
	}

}