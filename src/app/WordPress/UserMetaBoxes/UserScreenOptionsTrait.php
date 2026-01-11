<?php

namespace WPSPCORE\App\WordPress\UserMetaBoxes;

trait UserScreenOptionsTrait {

	/**
	 * Lấy screen layout columns.
	 */
	public function screenColumns() {
		$screenColumns = get_user_option('screen_layout_' . get_current_screen()->id) ?: 2;
		return $screenColumns;
	}

}