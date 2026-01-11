<?php

namespace WPSPCORE\app\WordPress\AdminPages;

trait AdminPageScreenOptionsTrait {

	public $showScreenOptions    = false;
	public $screenOptionsKey     = null;
	public $screenOptionsPageNow = null;

	/*
	 *
	 */

	/**
	 * Xử lý screen options.
	 */
	public function showScreenOptions() {
		/**
		 * Nếu menu hiện tại có thể hiển thị screen options.\
		 * Hãy hiển thị screen options khi truy cập.
		 */
		if ($this->showScreenOptions) {
			// Show screen options.
			add_filter('screen_options_show_screen', function() {
				return true;
			});

			// Thêm tùy chọn chia layout trên screen options panel.
			add_action('current_screen', function(\WP_Screen $screen) {
				add_screen_option('layout_columns', ['max' => 2, 'default' => 2]);
			}, 9999999999);

			// Custom screen options.
			add_action('current_screen', function($screen) {
				// Ghi đè "screen id" và "screen base".
				// Mục đích để screen options hoạt động độc lập theo "screen_options_key".
				$screen->id   = $this->screenOptionsKey;
				$screen->base = $this->screenOptionsKey;

				// Truyền thêm property này vào current screen để List Table có thể gọi ra.
				$screen->show_screen_options = true;
			}, 1);

			// Save items per page option.
			add_filter('set_screen_option_' . $this->screenOptionsKey . '_items_per_page', function($default, $option, $value) {
				return $value;
			}, 9999999999, 3);
		}
		/**
		 * Nếu không, ẩn hoàn toàn screen options.\
		 * Vì nếu menu hiện tại có chứa Custom List Table, screen options sẽ tự động hiển thị.
		 */
		else {
			add_filter('screen_options_show_screen', function() {
				return false;
			});
		}
	}

	/**
	 * Ghi đè "pagenow" trong JavaScript để gửi Ajax sắp xếp metaboxes.
	 */
	public function overrideScreenOptionsPageNow() {
		if ($this->screenOptionsPageNow || $this->screenOptionsKey) {
			add_action('admin_head', function() {
				echo '<script> var pagenow = "' . ($this->screenOptionsPageNow ?? $this->screenOptionsKey) . '"; </script>';
			}, 999999999);
		}
	}

	/**
	 * Lấy screen layout columns.
	 */
	public function screenColumns() {
		$screenColumns = get_user_option('screen_layout_' . ($this->screenOptionsPageNow ?? $this->screenOptionsKey)) ?: 2;
		return $screenColumns;
	}

}