<?php

namespace WPSPCORE\App\WordPress\ListTables;

use WPSPCORE\App\Traits\BaseInstancesTrait;

/**
 * @method bulk_edit_form()
 */
abstract class BaseListTable extends \WP_List_Table {

	use BaseInstancesTrait;

	public $args             = [];
	public $allowScreenIds   = null;
	public $itemsPerPageKey  = null;
	public $bulkEditAssets	 = true;

	private $currentScreen   = null;

	/*
	 *
	 */

	public function __construct($args = [], $allowScreenIds = null, $mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		global $current_screen;
		$this->currentScreen = $current_screen;

		$this->args           = $args;
		$this->allowScreenIds = $allowScreenIds ?? $this->allowScreenIds;

		// Cần gọi __construct của parent trước.
		parent::__construct($this->args);

		// Khởi tạo các thuộc tính cơ bản.
		$this->baseInstanceConstruct($mainPath, $rootNamespace, $prefixEnv, $extraParams);

		// Chuẩn hóa "allowScreenIds".
		if (!$this->allowScreenIds) {
			$this->allowScreenIds = $this->funcs?->_slugParams(['page']);
		}

		// Chuẩn hóa "itemsPerPageKey".
		if (!$this->itemsPerPageKey) {
			$this->itemsPerPageKey = $this->currentScreen?->id ?? $this->funcs?->_getAppShortName() ?? 'wpsp';
			$this->itemsPerPageKey .= '_items_per_page';
		}

		// Tự động đăng ký các checkboxes để ẩn/hiện cột cho Custom List Table trên Screen Options panel.
		$this->autoScreenOptionColumns();

		// List table này có bulk edit hay không.
		$this->maybeEnqueueBulkEditAssets();
	}

	/**
	 * Tự động đăng ký các checkboxes để ẩn/hiện cột cho Custom List Table trên Screen Options panel.
	 */
	public function autoScreenOptionColumns() {
//		add_action('current_screen', function(\WP_Screen $current_screen) {
			$currentScreenId   = $this->currentScreen?->id ?? null;
			$showScreenOptions = $this->currentScreen?->show_screen_options ?? false;
			if ($currentScreenId && $showScreenOptions) {
				/**
				 * Kiểm tra xem "screenId" hiện tại có khớp với "allowScreenIds" được khải báo trong Custom List Table không.
				 * Nếu có thì kích hoạt tính năng "hidden columns" và "items per page" trên "sreen options panel".
				 */
				$isScreenMatched = false;

				// Nếu screenIds là mảng.
				if (is_array($this->allowScreenIds)) {
					foreach ($this->allowScreenIds as $allowScreenId) {
						// Nếu mỗi "screenId" bắt đầu bằng "/", xem như đó là Regex.
						if (str_starts_with($allowScreenId, '/') && preg_match($allowScreenId, $currentScreenId)) {
							$isScreenMatched = true;
							break;
						}
						// Nếu không, nó là chuỗi.
						elseif ($allowScreenId === $currentScreenId) {
							$isScreenMatched = true;
							break;
						}
					}
				}
				// Nếu screenIds là một chuỗi.
				elseif (is_string($this->allowScreenIds) && $currentScreenId == $this->allowScreenIds) {
					$isScreenMatched = true;
				}

				if (!$isScreenMatched) return;

				// Hiển thị tính năng hide columns trên screen options panel.
				add_filter("manage_{$currentScreenId}_columns", function($columns) {
					return array_merge($columns, $this->get_columns());
				});

				// Items per page độc lập theo mỗi screen.
				add_screen_option('per_page', [
					'default' => 20,
					'option'  => $this->itemsPerPageKey,
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
	 * - List table có method "bulk_edit_form" được định nghĩa
	 * - Thuộc tính bulkEditAssets được set là true
	 *
	 * @return void
	 */
	public function maybeEnqueueBulkEditAssets() {
		if (method_exists($this, 'bulk_edit_form') && $this->bulkEditAssets) {
			wp_register_script('wpsp-bulk-edit',
				$this->funcs->asset('widen/custom/js/bulk-edit.js'),
				['jquery'],
				$this->funcs->_getVersion(),
				['in_footer' => true]
			);
			add_action('admin_enqueue_scripts', function() {
				wp_enqueue_script('wpsp-bulk-edit');
			});
		}
	}

}