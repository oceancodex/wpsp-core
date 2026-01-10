<?php

namespace WPSPCORE\App\WordPress\AdminPages;

trait AdminPageMetaboxesTrait {

	/**
	 * Lấy danh sách admin page metaboxes theo thứ tự đã lưu trong user meta.
	 */
	public function adminPageMetaboxes() {
		$pageNow                  = $this->adminPageMetaboxesPageNow ?? $this->screenOptionsKey ?? null;
		$metaboxes                = $this->adminPageMetaboxes ?? [];
		$metaboxesListBoxes       = array_keys(array_unique($metaboxes));
		$sortedAdminPageMetaboxes = ['side' => [], 'normal' => [], 'advanced' => [], 'closed' => []];

		if ($pageNow && $metaboxes) {
			$orderMetaboxes  = get_user_option('meta-box-order_' . $pageNow);
			$closedMetaboxes = get_user_option('closedpostboxes_' . $pageNow);

			/**
			 * [1] Chuẩn bị danh sách metaboxes lấy từ user meta.\
			 * Danh sách này có dạng [0 => 'submitdiv', 1 => 'inputsdiv', ...]\
			 * Mục đích để lấy ra các metaboxes khác nhau giữa metaboxes lấy từ user và metaboxs được khai báo trong admin page.
			 */
			$orderMetaboxesListBoxes = [];
			foreach ($orderMetaboxes as $position => $metaboxIds) {
				$metaboxIds = explode(',', $metaboxIds);
				foreach ($metaboxIds as $metaboxId) {
					if ($metaboxId) {
						// Lưu danh sách metaboxes theo thứ tự.
						if (isset($metaboxes[$metaboxId])) {
							$sortedAdminPageMetaboxes[$position][$metaboxId] = $metaboxes[$metaboxId];
						}

						// Thêm các item vào danh sách [1]
						$orderMetaboxesListBoxes[] = $metaboxId;
					}
				}
			}

			/**
			 * [2] Lấy các metaboxes khác nhau giữa metaboxes lấy từ user và metaboxs được khai báo trong admin page.
			 * Sau đó đưa vào danh sách sorted admin meta boxes.
			 */
			$leftoverMetaboxes = array_values(array_diff($metaboxesListBoxes, $orderMetaboxesListBoxes));
			foreach ($leftoverMetaboxes as $metaboxId) {
				$sortedAdminPageMetaboxes['normal'][$metaboxId] = $metaboxes[$metaboxId];
			}

			/**
			 * [3] Đóng các metaboxes đã đưa vào danh sách closed.
			 */
			foreach ($closedMetaboxes as $closedMetabox) {
				$sortedAdminPageMetaboxes['closed'][$closedMetabox] = 1;
			}
		}

		return $sortedAdminPageMetaboxes;
	}

}