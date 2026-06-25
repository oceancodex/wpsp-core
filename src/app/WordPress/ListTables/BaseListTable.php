<?php

namespace WPSPCORE\App\WordPress\ListTables;

use WPSPCORE\App\Traits\BaseInstancesTrait;

abstract class BaseListTable extends \WP_List_Table {

	use BaseInstancesTrait;

	public $args             = [];
	public $screenIds        = null;
	public $screenOptionsKey = null;
	public $bulkEditAssets	 = true;

	/*
	 *
	 */

	public function __construct($args = [], $screenIds = null, $mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		$this->args      = $args;
		$this->screenIds = $screenIds;

		// Cần gọi __construct của parent trước.
		parent::__construct($this->args);

		// Khởi tạo các thuộc tính cơ bản.
		$this->baseInstanceConstruct($mainPath, $rootNamespace, $prefixEnv, $extraParams);

		// Chuẩn hóa "screenOptionsKey".
		if (!$this->screenOptionsKey) {
			$this->screenOptionsKey = $this->funcs->_slugParams(['page']) ?? $screenIds;
		}

		// Tự động đăng ký các checkboxes để ẩn/hiện cột cho Custom List Table trên Screen Options panel.
		$this->autoScreenOptionColumns();
		
		// List table này có bulk edit hay không.
		$this->maybeBulkEdit();
	}

	/**
	 * Tự động đăng ký các checkboxes để ẩn/hiện cột cho Custom List Table trên Screen Options panel.
	 */
	public function autoScreenOptionColumns() {
		global $current_screen;

//		add_action('current_screen', function(\WP_Screen $current_screen) {
			$screenId          = $current_screen?->id ?? null;
			$showScreenOptions = $current_screen?->show_screen_options ?? false;
			if ($screenId && $showScreenOptions) {
				// Nếu screen ID hiện tại không khớp với screenOptionsKey của list table, không khởi tạo sreen option columns và items per page.
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
//		}, 9999999999);
	}

	/**
	 * Redirects the request after processing a bulk action by removing unnecessary query variables
	 * from the URL to prevent repetition of the same actions.
	 *
	 * @return void
	 */
	public function redirectBulkActions($removeQueryVars = [], $params = []) {
		$defaultRemoveQueryVars = [
			'_wp_http_referer',
			'_wpnonce',
			'action',
			'action2',
			'bulk_action',
		];

		$removeQueryVars = array_merge($defaultRemoveQueryVars, $removeQueryVars);

		if (isset($_REQUEST['action']) && isset($_REQUEST['action2'])) {
			$url = remove_query_arg($removeQueryVars, stripslashes($_SERVER['REQUEST_URI']));

			if (!empty($params)) {
				$url = add_query_arg($params, $url);
			}

			wp_safe_redirect($url);
			exit;
		}
	}

	/**
	 * Kiểm tra và đăng ký assets cho chức năng bulk edit nếu list table hỗ trợ.
	 *
	 * Method này sẽ tự động đăng ký và enqueue script bulk-edit.js nếu:
	 * - List table có method bulk_edit() được định nghĩa
	 * - Thuộc tính bulkEditAssets được set là true
	 *
	 * @return void
	 */
	public function maybeBulkEdit() {
		if (method_exists($this, 'bulk_edit') && $this->bulkEditAssets) {
			wp_register_script('wpsp-bulk-edit',
				$this->funcs->asset('widen/custom/js/bulk-edit.js'),
				['jquery'],
				$this->funcs->_getVersion(),
				['in_footer' => true]);
			add_action('admin_enqueue_scripts', function() {
				wp_enqueue_script('wpsp-bulk-edit');
			});
		}
	}

}