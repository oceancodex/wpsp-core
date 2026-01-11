<?php

namespace WPSPCORE\App\WordPress\AdminPages;

trait AdminPageMetaboxesTrait {

	public $adminPageMetaboxes         = [];
	public $adminPageMetaboxesSortable = false;
	public $adminPageMetaboxesPageNow  = null;

	/**
	 * Lấy danh sách admin page metaboxes theo thứ tự đã lưu trong user meta.
	 */
	public function adminPageMetaboxes() {
		$pageNow                  = $this->adminPageMetaboxesPageNow ?? $this->screenOptionsKey ?? null;
		$metaboxes                = $this->adminPageMetaboxes ?? [];
		$sortedAdminPageMetaboxes = ['side' => [], 'normal' => [], 'advanced' => [], 'closed' => [], 'hidden' => []];

		if ($pageNow && $metaboxes) {
			/**
			 * [1] Chuẩn bị danh sách metaboxes lấy từ admin page.\
			 * Danh sách này có dạng [0 => 'submitdiv', 1 => 'inputsdiv', ...]\
			 * Mục đích để lấy ra các metaboxes khác nhau giữa metaboxes lấy từ user và metaboxs được khai báo trong admin page.
			 */
			$metaboxesListBoxes = [];
			foreach ($metaboxes as $position => $metaboxItems) {
				foreach ($metaboxItems as $metaboxItemKey => $metaboxItemValue) {
					$metaboxesListBoxes[$metaboxItemKey] = $metaboxItemValue;
				}
			}

			$orderMetaboxes  = get_user_option('meta-box-order_' . $pageNow) ?: [];
			$closedMetaboxes = get_user_option('closedpostboxes_' . $pageNow) ?: [];
			$hiddenMetaboxes = get_user_option('metaboxhidden_' . $pageNow) ?: [];

			if (!empty($orderMetaboxes)) {
				/**
				 * [2] Chuẩn bị danh sách metaboxes lấy từ user meta.\
				 * Danh sách này có dạng [0 => 'submitdiv', 1 => 'inputsdiv', ...]\
				 * Mục đích để lấy ra các metaboxes khác nhau giữa metaboxes lấy từ user và metaboxs được khai báo trong admin page.
				 */
				$orderMetaboxesListBoxes = [];
				foreach ($orderMetaboxes as $position => $metaboxIds) {
					$metaboxIds = explode(',', $metaboxIds);
					foreach ($metaboxIds as $metaboxId) {
						if ($metaboxId) {
							// Lưu danh sách metaboxes theo thứ tự.
							if (isset($metaboxesListBoxes[$metaboxId])) {
								$sortedAdminPageMetaboxes[$position][$metaboxId] = $metaboxesListBoxes[$metaboxId];
							}

							// Thêm các item vào danh sách [1]
							$orderMetaboxesListBoxes[] = $metaboxId;
						}
					}
				}

				/**
				 * [3] Lấy các metaboxes khác nhau giữa metaboxes lấy từ user và metaboxs được khai báo trong admin page.
				 * Sau đó đưa vào danh sách sorted admin meta boxes.
				 */
				$leftoverMetaboxes = array_values(array_diff(array_keys($metaboxesListBoxes), $orderMetaboxesListBoxes));
				foreach ($leftoverMetaboxes as $metaboxId) {
					$sortedAdminPageMetaboxes['normal'][$metaboxId] = $metaboxes[$metaboxId];
				}
			}
			else {
				$sortedAdminPageMetaboxes = $metaboxes;
			}

			/**
			 * [4] Đóng các metaboxes đã đưa vào danh sách closed.
			 */
			foreach ($closedMetaboxes as $closedMetabox) {
				$sortedAdminPageMetaboxes['closed'][$closedMetabox] = 1;
			}

			/**
			 * [5] Ẩn các metaboxes đã đưa vào danh sách hidden.
			 */
			foreach ($hiddenMetaboxes as $hiddenMetabox) {
				$sortedAdminPageMetaboxes['hidden'][$hiddenMetabox] = 1;
			}
		}

		return $sortedAdminPageMetaboxes;
	}

	/**
	 * Ghi đè "pagenow" trong JavaScript để gửi Ajax sắp xếp metaboxes.
	 */
	public function overridePageNowForOrderAdminMetaBoxes() {
		if ($this->adminPageMetaboxesPageNow || $this->screenOptionsKey) {
			add_action('admin_head', function() {
				echo '<script> var pagenow = "' . ($this->adminPageMetaboxesPageNow ?? $this->screenOptionsKey) . '"; </script>';
			}, 999999999);
		}
	}

}