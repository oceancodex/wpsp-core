<?php

namespace WPSPCORE\App\WordPress\ListTables;

use WPSPCORE\App\Traits\BaseInstancesTrait;

abstract class BaseListTable extends \WP_List_Table {

	use BaseInstancesTrait;

	public $removeQueryVars = [];

	public function __construct($args = [], $mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		// Cần gọi __construct của parent trước.
		parent::__construct($args);

		// Khởi tạo các thuộc tính cơ bản.
		$this->baseInstanceConstruct($mainPath, $rootNamespace, $prefixEnv, $extraParams);

		// Tự động đăng ký các checkboxes để ẩn/hiện cột cho Custom List Table trên Screen Options panel.
		$this->autoRegisterColumns();
	}

	/**
	 * Tự động đăng ký các checkboxes để ẩn/hiện cột cho Custom List Table trên Screen Options panel.
	 */
	public function autoRegisterColumns() {
		add_action('current_screen', function (\WP_Screen $screen) {
			$screenId = $screen->id;
			$showScreenOptions = $screen->show_screen_options ?? false;
			if ($showScreenOptions) {
				add_filter("manage_{$screenId}_columns", function($columns) {
					return $this->get_columns();
				});
			}
		}, 20);
	}

}