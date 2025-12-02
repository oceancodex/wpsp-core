<?php

namespace WPSPCORE\App\WP\UserMetaBoxes;

use WPSPCORE\App\BaseInstances;

abstract class BaseUserMetaBox extends BaseInstances {

	public $id                = null;
	public $title             = null;
	public $update            = false;
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'] ?? null;
		$this->overrideId($this->extraParams['id'] ?? null);
		$this->customProperties();

		// Update meta boxes.
		if ($this->update) $this->updateUser();

		// Enqueue scripts and styles.
		if ($this->isUserEditPage()) {
			$this->styles();
			$this->scripts();
			$this->localizeScripts();
		}
	}

	/*
	 *
	 */

	private function overrideId($id = null) {
		if ($id && !$this->id) {
			$this->id = $id;
		}
	}

	private function updateUser() {
		add_action('personal_options_update', [$this, 'update']);
		add_action('edit_user_profile_update', [$this, 'update']);
	}

	private function isUserEditPage($type = null): bool {
		global $pagenow;

		if ($type === 'profile') {
			return $pagenow === 'profile.php';
		}

		if ($type === 'edit') {
			return $pagenow === 'user-edit.php';
		}

		return in_array($pagenow, ['user-edit.php', 'profile.php'], true);
	}

	/*
	 *
	 */

	abstract public function index($user);

	abstract public function update($userId);

	/*
	 *
	 */

	abstract public function styles();

	abstract public function scripts();

	abstract public function localizeScripts();

}