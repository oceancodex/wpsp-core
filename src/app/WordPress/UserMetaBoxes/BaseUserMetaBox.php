<?php

namespace WPSPCORE\App\WordPress\UserMetaBoxes;

use WPSPCORE\BaseInstances;

abstract class BaseUserMetaBox extends BaseInstances {

	public  $id                   = null;
	public  $title                = null;

	public  $update_priority      = 10;
	public  $update_accepted_args = 1;

	public  $callback_function    = null;

	private $path                 = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideId($this->extraParams['full_path'] ?? null);
		$this->path = $this->extraParams['path'] ?? null;

		// Update meta boxes.
		if (method_exists($this, 'update')) {
			$this->updateUser();
		}

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

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overrideId($id = null) {
		if ($id && !$this->id) {
			$this->id = $id;
		}
	}

	/*
	 *
	 */

	public function init($id = null) {
		$requestPath = ltrim($this->request->getRequestUri(), '/\\');
		$id          = $this->id ?? $id;

		if ($id) {
			$callback = function($user) use ($requestPath, $id) {
				return $this->autoResolveAndCall($this->path, $id, $requestPath, $this, $this->callback_function, [
					'user'  => $user,
					'id'    => $id,
					'title' => $this->title,
				]);
			};

			add_action('show_user_profile', $callback, $this->extraParams['priority'] ?? 10, $this->extraParams['accepted_args'] ?? 1);
			add_action('edit_user_profile', $callback, $this->extraParams['priority'] ?? 10, $this->extraParams['accepted_args'] ?? 1);
		}
	}

	/*
	 *
	 */

	private function updateUser() {
		$requestPath = ltrim($this->request->getRequestUri(), '/\\');
		$callback    = function($user_id) use ($requestPath) {
			return $this->autoResolveAndCall($this->path, $this->id, $requestPath, $this, 'update', ['user_id' => $user_id]);
		};

		add_action('personal_options_update', $callback, $this->update_priority ?? $this->extraParams['priority'] ?? 10, $this->update_accepted_args ?? $this->extraParams['accepted_args'] ?? 1);
		add_action('edit_user_profile_update', $callback, $this->update_priority ?? $this->extraParams['priority'] ?? 10, $this->update_accepted_args ?? $this->extraParams['accepted_args'] ?? 1);
	}

	private function isUserEditPage($type = null) {
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

	abstract public function styles();

	abstract public function scripts();

	abstract public function localizeScripts();

}