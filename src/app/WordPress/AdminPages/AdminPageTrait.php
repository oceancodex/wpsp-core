<?php

namespace WPSPCORE\App\WordPress\AdminPages;

trait AdminPageTrait {

	public $page_title_override = null;

	/**
	 * Ghi đè page_title.
	 */
	public function overridePageTitle($overrideTitle = null) {
		$overrideTitle = $overrideTitle ?? $this->page_title_override ?? $this->page_title;
		if ($overrideTitle) {
			add_filter('admin_title', function($admin_title, $title) use ($overrideTitle) {
				return $overrideTitle;
			}, 9999999999, 2);
		}
	}

	/**
	 * Gọi phương thức trong admin page class nếu tồn tại.\
	 * Trước hết cần prepare method để DI.\
	 * Sau đó gọi phương thức với DI.
	 */
	public function callAdminPageMethod($method) {
		if (method_exists($this, $method)) {
			$callback = $this->prepareCallbackFunction($method, $this->menu_slug, $this->menu_slug);
			$this->resolveAndCall($callback);
		}
	}

	/**
	 * Xử lý admin menu classes.
	 */
	private function handleAdminMenuClasses($additionalClasses = null) {
		/**
		 * Khi có "additionalClasses", xử lý nó.\
		 * Khi menu có khai báo $classes, xử lý nó.
		 */
		if ($additionalClasses = $additionalClasses ?? $this->classes) {
			if ($this->isSubmenuPage) {
				add_action('admin_menu', function() use ($additionalClasses) {
					global $submenu;

					if (!isset($submenu[$this->parent_slug])) {
						return;
					}

					foreach ($submenu[$this->parent_slug] as $index => &$item) {
						if ($item[2] === $this->menu_slug) {
							$item[4] = $this->prepareAdminMenuClasses($item[4] ?? '', $additionalClasses);
						}
					}
				}, 9999999999);
			}
			else {
				add_action('admin_menu', function() use ($additionalClasses) {
					global $menu;

					foreach ($menu as $index => &$item) {
						if ($item[2] === $this->menu_slug) {
							$item[4] = $this->prepareAdminMenuClasses($item[4] ?? '', $additionalClasses);
							break;
						}
					}
				}, 9999999999);
			}
		}

		/**
		 * Khi menu có nhiều submenu, WordPress sẽ tự sinh submenu cho trang chính ở vị trí đầu tiên.\
		 * Xử lý class="" cho submenu tự sinh.
		 */
		if ($this->firstSubmenuClasses) {
			add_action('admin_menu', function() {
				global $submenu;

				if (!isset($submenu[$this->menu_slug])) {
					return;
				}

				foreach ($submenu[$this->menu_slug] as $index => &$item) {
					if ($item[2] === $this->menu_slug) {
						$item[4] = $this->prepareAdminMenuClasses($item[4] ?? '', $this->firstSubmenuClasses);
					}
				}
			}, 9999999999);
		}
	}

	/**
	 * Chuẩn bị classes cho admin menu.
	 */
	private function prepareAdminMenuClasses($currentClasses = null, $additionalClasses = null) {
		$currentClasses = trim($currentClasses);

		if (!$additionalClasses) {
			return $currentClasses;
		}

		// CASE 1: string → append
		if (is_string($additionalClasses)) {
			return trim($currentClasses . ' ' . $additionalClasses);
		}

		// CASE 2 / 3 / 4: array
		if (is_array($additionalClasses)) {

			// CASE 3: ['find'=>..., 'replace'=>...]
			if (isset($additionalClasses['find'], $additionalClasses['replace'])) {
				return trim(str_replace(
					$additionalClasses['find'],
					$additionalClasses['replace'],
					$currentClasses
				));
			}

			// CASE 4: [ ['find'=>..., 'replace'=>...], ... ]
			$isReplaceList = isset($additionalClasses[0]['replace']) && isset($additionalClasses[0]['find']) && is_array($additionalClasses[0]);
			if ($isReplaceList) {
				foreach ($additionalClasses as $rule) {
					$currentClasses = str_replace(
						$rule['find'],
						$rule['replace'],
						$currentClasses
					);
				}
				return trim($currentClasses);
			}

			// CASE 2: ['class-1','class-2'] → append
			return trim($currentClasses . ' ' . implode(' ', $additionalClasses));
		}

		return $currentClasses;
	}

}