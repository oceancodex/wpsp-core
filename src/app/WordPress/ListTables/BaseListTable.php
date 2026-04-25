<?php

namespace WPSPCORE\App\WordPress\ListTables;

use WPSPCORE\App\Traits\BaseInstancesTrait;

abstract class BaseListTable extends \WP_List_Table {

	use BaseInstancesTrait;

	public $screenOptionsKey = null;

	/*
	 *
	 */

	public function __construct($args = [], $screenIds = null, $mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		// Cần gọi __construct của parent trước.
		parent::__construct($args);

		// Khởi tạo các thuộc tính cơ bản.
		$this->baseInstanceConstruct($mainPath, $rootNamespace, $prefixEnv, $extraParams);

		// Chuẩn hóa "screenOptionsKey".
		if (!$this->screenOptionsKey) {
			$this->screenOptionsKey = $this->funcs->_slugParams(['page']) ?? $screenIds;
		}

		// Tự động đăng ký các checkboxes để ẩn/hiện cột cho Custom List Table trên Screen Options panel.
		$this->autoScreenOptionColumns();
	}

	/**
	 * Tự động đăng ký các checkboxes để ẩn/hiện cột cho Custom List Table trên Screen Options panel.
	 */
	public function autoScreenOptionColumns() {
		add_action('current_screen', function (\WP_Screen $screen) {
			$screenId = $screen->id;
			$showScreenOptions = $screen->show_screen_options ?? false;
			if ($showScreenOptions) {
				// Nếu screen ID hiện tại không khớp với screenOptionsKey của list table, không khởi tạo sreen options panel.
				if (is_array($this->screenOptionsKey)) {
					if (!in_array($screenId, $this->screenOptionsKey)) return;
				}
				elseif (is_string($this->screenOptionsKey)) {
					if ($screenId !== $this->screenOptionsKey) return;
				}

				add_filter("manage_{$screenId}_columns", function($columns) {
					$columns = array_merge($columns, $this->get_columns());
					return $columns;
				});

				// Items per page độc lập theo "screen_options_key".
				add_screen_option('per_page', [
					'default' => 20,
					'option'  => $screenId . '_items_per_page',
				]);
			}
		}, 9999999999);
	}

}