<?php

namespace WPSPCORE\App\WordPress\AdminPages;

trait AdminPageScreenOptionsTrait {

	public $showScreenOptions = false;
	public $screenBase        = null;
	public $screenId          = null;
	public $pagenow           = null;
	public $itemsPerPageKey   = null;

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
			}, 9999999999);

			// Thêm tùy chọn chia layout trên screen options panel.
			add_action('current_screen', function(\WP_Screen $screen) {
				add_screen_option('layout_columns', ['max' => 2, 'default' => 2]);
			}, 9999999999);

			// Truyền thêm "show_screen_options" vào current screen để List Table có thể gọi ra.
			add_action('current_screen', function($screen) {
				$screen->show_screen_options = true;
			}, 2);

			// Chuẩn hóa "itemsPerPageKey".
			if (!$this->itemsPerPageKey) {
				$this->itemsPerPageKey = $this->screenId ?? $this->funcs?->_getAppShortName() ?? 'wpsp';
				$this->itemsPerPageKey .= '_items_per_page';
			}

			// Lưu giá trị items per page.
			add_filter('set_screen_option_'.$this->itemsPerPageKey, function($default, $option, $value) {
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
	 * Ghi đè "current screen".
	 */
	public function overrideCurrentScreen() {
		if ($this->screenBase || $this->screenId) {
			add_action('current_screen', function($screen) {
				$screen->id   = $this->screenId;
				$screen->base = $this->screenBase ?? $this->screenId;
			}, 1);
		}
	}

	/**
	 * Ghi đè "pagenow" trong JavaScript để gửi Ajax sắp xếp metaboxes.
	 */
	public function overridePageNow() {
		if ($this->pagenow) {
			add_action('admin_head', function() {
				echo '<script> var pagenow = "'.$this->pagenow.'"; </script>';
			}, 9999999999);
		}
	}

	/**
	 * Lấy screen layout columns.
	 */
	public function getScreenColumns() {
		$screenColumns = get_user_option('screen_layout_'.($this->pagenow ?? $this->screenId)) ?: 2;
		return $screenColumns;
	}

}