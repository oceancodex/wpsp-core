<?php

namespace WPSPCORE\App\WordPress\AdminPages;

trait AdminPageMetaBoxesTrait {

	public $adminPageMetaBoxes  = [];

	/**
	 * Lấy danh sách admin page metaboxes theo thứ tự đã lưu trong user meta.
	 */
	public function getSortedAdminPageMetaBoxes() {
		$pageNow                  = $this->screenOptionsPageNow ?? $this->screenOptionsKey ?? null;
		$metaboxes                = $this->adminPageMetaBoxes ?? [];
		$sortedAdminPageMetaBoxes = ['side' => [], 'normal' => [], 'advanced' => [], 'closed' => [], 'hidden' => []];

		if ($pageNow && $metaboxes) {
			$metaboxesDefault = [];
			foreach ($metaboxes as $position => $metaboxItems) {
				if ($position === 'side' || $position === 'advanced' || $position === 'normal') {
					$metaboxesDefault[$position] = implode(',', array_keys($metaboxItems));
				}
			}

			/**
			 * [1] Chuẩn bị danh sách metaboxes lấy từ admin page.\
			 * Danh sách này có dạng ['submitdiv' => ['title' => '', 'view' => ''], ...]\
			 * Mục đích để lấy ra các metaboxes khác nhau giữa metaboxes lấy từ user và metaboxs được khai báo trong admin page.
			 */
			$metaboxesList = [];
			foreach ($metaboxes as $position => $metaboxItems) {
				if ($position === 'side' || $position === 'advanced' || $position === 'normal') {
					foreach ($metaboxItems as $metaboxItemKey => $metaboxItem) {
						$metaboxesList[$metaboxItemKey] = $metaboxItem;
					}
				}
			}

			$sortedMetaBoxes = get_user_option('meta-box-order_' . $pageNow) ?: $metaboxesDefault;
			$closedMetaBoxes = get_user_option('closedpostboxes_' . $pageNow);
			$hiddenMetaBoxes = get_user_option('metaboxhidden_' . $pageNow);
//			$screenColumns   = get_user_option('screen_layout_' . $pageNow) ?: 2;

			if (!empty($sortedMetaBoxes)) {
				/**
				 * [2] Chuẩn bị danh sách metaboxes lấy từ user meta.\
				 * Danh sách này có dạng [0 => 'submitdiv', 1 => 'inputsdiv', ...]\
				 * Mục đích để lấy ra các metaboxes khác nhau giữa metaboxes lấy từ user và metaboxs được khai báo trong admin page.
				 */
				$sortedMetaBoxesList = [];
				foreach ($sortedMetaBoxes as $position => $metaboxStrIds) {
					$metaboxIds = explode(',', $metaboxStrIds);
					foreach ($metaboxIds as $metaboxId) {
						if ($metaboxId) {
							// Lưu danh sách metaboxes theo thứ tự.
							if (isset($metaboxesList[$metaboxId])) {
								$sortedAdminPageMetaBoxes[$position][$metaboxId] = $metaboxesList[$metaboxId];
							}

							// Thêm các item vào danh sách [1]
							$sortedMetaBoxesList[] = $metaboxId;
						}
					}
				}
				$sortedMetaBoxesList = array_unique($sortedMetaBoxesList);

				/**
				 * Tạo metaboxes ảo để hiển thị checkboxes trên screen options panel.
				 */
				add_action('current_screen', function($screen) use ($pageNow, $metaboxesList) {
					foreach ($metaboxesList as $metaboxId => $metabox) {
						add_meta_box(
							$metaboxId,
							$metabox['title'] ?? null,
							fn() => null,
							$pageNow,
							'normal'
						);
					}
				});

				/**
				 * [3] Lấy các metaboxes khác nhau giữa metaboxes lấy từ user và metaboxs được khai báo trong admin page.
				 * Sau đó đưa vào danh sách sorted admin meta boxes.
				 */
				$leftoverMetaBoxes = array_values(array_diff(array_keys($metaboxesList), $sortedMetaBoxesList));
				foreach ($leftoverMetaBoxes as $leftoverMetaBoxId) {
					if (isset($metaboxesList[$leftoverMetaBoxId])) {
						$sortedAdminPageMetaBoxes['normal'][$leftoverMetaBoxId] = $metaboxesList[$leftoverMetaBoxId];
					}
				}
			}
			else {
				$sortedAdminPageMetaBoxes = $metaboxes;
			}

			/**
			 * [4] Đóng các metaboxes đã đưa vào danh sách closed.
			 */
			if (isset($closedMetaBoxes) && is_array($closedMetaBoxes)) {
				$sortedAdminPageMetaBoxes['closed'] = [];
				foreach ($closedMetaBoxes as $closedMetaBox) {
					$sortedAdminPageMetaBoxes['closed'][$closedMetaBox] = 1;
				}
			}
			else {
				$sortedAdminPageMetaBoxes['closed'] = $metaboxes['closed'] ?? [];
			}

			/**
			 * [5] Ẩn các metaboxes đã đưa vào danh sách hidden.
			 */
			if (isset($hiddenMetaBoxes) && is_array($hiddenMetaBoxes)) {
				$sortedAdminPageMetaBoxes['hidden'] = [];
				foreach ($hiddenMetaBoxes as $hiddenMetaBox) {
					$sortedAdminPageMetaBoxes['hidden'][$hiddenMetaBox] = 1;
				}
			}
			else {
				$sortedAdminPageMetaBoxes['hidden'] = $metaboxes['hidden'] ?? [];
				update_user_meta(get_current_user_id(), 'metaboxhidden_' . $pageNow, array_keys($metaboxes['hidden']));
			}
		}

		return $sortedAdminPageMetaBoxes;
	}

}