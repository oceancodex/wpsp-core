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

		add_action('current_screen', function() {
			$this->autoRegisterColumns();
		});

		// Xóa các query vars thừa.
		$this->removeQueryVars();
	}

	/**
	 * Xóa các query vars như _wpnonce, action, action2, _wp_http_referer, id, v.v… khỏi URL bằng cách redirect sang URL sạch.
	 */
	public function removeQueryVars() {
		if (
			isset($_REQUEST['action']) && $_REQUEST['action'] < 0 && isset($_REQUEST['action2']) && $_REQUEST['action2'] < 0
			|| !isset($_REQUEST['action']) && !isset($_REQUEST['action2']) && isset($_REQUEST['_wpnonce'])
		) {
			wp_safe_redirect(remove_query_arg($this->removeQueryVars, stripslashes($_SERVER['REQUEST_URI'])));
			exit;
		}
	}

	/**
	 * Tự động đăng ký các checkboxes để ẩn/hiện cột cho Custom List Table trên Screen Options panel.
	 */
	public function autoRegisterColumns() {
		$screen = get_current_screen();
		if (!$screen) return;

		$screen_id = $screen->id;

		// (1) Columns definitions
		add_filter("manage_{$screen_id}_columns", function($cols) {
			return $this->get_columns();
		});

		// (2) Load hidden columns from WP usermeta
//		add_filter("manage_{$screen_id}_columnshidden", function($hidden) use ($screen_id) {
//			return get_user_meta(
//				get_current_user_id(),
//				"manage_{$screen_id}_columnshidden",
//				true
//			) ?: [];
//		});
	}

}