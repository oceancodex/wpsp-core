<?php

namespace WPSPCORE\App\WordPress\AdminPages;

use WPSPCORE\App\Routes\RouteTrait;
use WPSPCORE\BaseInstances;

abstract class BaseAdminPage extends BaseInstances {

	use RouteTrait;

	/**
	 * WordPress admin page properties.
	 */
	public $menu_title             = null;
	public $page_title             = null;
	public $capability             = null;
	public $menu_slug              = null;
	public $icon_url               = null;
	public $position               = null;
	public $parent_slug            = null;

	public $classes                = null;
	public $firstSubmenuTitle      = null;
	public $firstSubmenuClasses    = null;
	public $isSubmenuPage          = false;
	public $removeFirstSubmenu     = false;
	public $urlsMatchCurrentAccess = [];
	public $urlsMatchHighlightMenu = [];
	public $showScreenOptions      = false;
	public $screenOptionsKey       = null;

	public $callback_function = null;

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideMenuSlug($this->extraParams['full_path']);
		if (!$this->screenOptionsKey) {
			$this->screenOptionsKey = $this->funcs->_slugParams(['page']) ?? $this->menu_slug;
		}
	}

	/*
	 *
	 */

	public function overrideMenuSlug($menu_slug = null) {
		if ($menu_slug && !$this->menu_slug) {
			$this->menu_slug = $menu_slug;
		}
	}

	/*
	 *
	 */

	public function init() {
		$this->beforeInit();
		$this->addAdminMenuPage();
		$this->handleAdminMenuClasses();
		$this->matchHighlightMenu();
		$this->matchCurrentAccess();
		$this->afterInit();
	}

	/*
	 *
	 */

	private function addMenuPage() {
		$callback = null;
		if ($this->callback_function && method_exists($this, $this->callback_function)) {
			$requestPath = trim($this->request->getRequestUri(), '/\\');
			$callback = $this->prepareCallbackFunction($this->callback_function, $this->menu_slug, $this->extraParams['full_path'] ?? $this->menu_slug);
//			$callParams = $this->getCallParams($this->extraParams['path'], $this->extraParams['full_path'], $requestPath, $this, $this->callback_function);
//			$callback = $this->resolveCallback($callback, $callParams);
		}

		$menuPage = add_menu_page(
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->menu_slug,
			$callback,
			$this->icon_url,
			$this->position
		);

		// Khi có nhiều submenu, WordPress sẽ tự sinh submenu cho trang chính.
		// Thay đổi tên submenu tự sinh.
		if ($this->firstSubmenuTitle) {
			remove_submenu_page($this->menu_slug, $this->menu_slug); // Xóa submenu tự sinh
			add_submenu_page(
				$this->menu_slug,
				$this->page_title,
				$this->firstSubmenuTitle,
				$this->capability,
				$this->menu_slug,
				$callback,
				$this->position
			);
		}

		return $menuPage;
	}

	private function addSubMenuPage() {
		$callback = null;
		if ($this->callback_function && method_exists($this, $this->callback_function)) {
			$requestPath = trim($this->request->getRequestUri(), '/\\');
			$callback = $this->prepareCallbackFunction($this->callback_function, $this->menu_slug, $this->extraParams['full_path'] ?? $this->menu_slug);
//			$callParams = $this->getCallParams($this->extraParams['path'], $this->extraParams['full_path'], $requestPath, $this, $this->callback_function);
//			$callback = $this->resolveCallback($callback, $callParams);
		}

		$subMenuPage = add_submenu_page(
			$this->parent_slug,
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->menu_slug,
			$callback,
			$this->position
		);

		return $subMenuPage;
	}

	private function addAdminMenuPage() {
		add_action('admin_menu', function() {
			$adminPage = $this->isSubmenuPage ? $this->addSubMenuPage() : $this->addMenuPage();

			// Hook sau khi add admin menu page hoặc submenu page.
			$this->afterAddAdminPage($adminPage);

			// Hook sau trước khi load admin page.
			$this->beforeLoadAdminPage($adminPage);

			/**
			 * Action "load-{admin_page}" chỉ hoạt động với admin menu page được register với slug chuẩn WordPress. Ví dụ: "edit.php", "post-new.php", hoặc "my_custom_page".\
			 * Với các dạng slug khác như: "wpsp&tab=tab-1", action này không hoạt động.
			 */
			add_action('load-' . $adminPage, function() use ($adminPage) {
				$this->beforeInLoadAdminPage($adminPage);

				// Enqueue scripts.
				add_action('admin_enqueue_scripts', [$this, 'assets']);

				$this->afterInLoadAdminPage($adminPage);
			});

			$this->afterLoadAdminPage($adminPage);
		});

		/**
		 * Khi menu có nhiều submenu, WordPress sẽ tự sinh submenu cho trang chính ở vị trí đầu tiên.\
		 * Loại bỏ submenu tự sinh này khỏi danh sách menu items.
		 */
		if ($this->removeFirstSubmenu) {
			add_action('admin_menu', function() {
				remove_submenu_page($this->menu_slug, $this->menu_slug);
			}, 9999999999);
		}
	}

	private function matchHighlightMenu() {
		$currentRequest = $this->request->getRequestUri();

		/**
		 * ---
		 * Tùy chọn khớp với request hiện tại.
		 * ---
		 * Xử lý "urlsMatchHighlightMenu".\
		 * Nếu có một trong các url khớp với request hiện tại,\
		 * thì highlight submenu nơi khai báo "urlsMatchHighlightMenu".
		 */
		if (!empty($this->urlsMatchHighlightMenu) && is_array($this->urlsMatchHighlightMenu)) {
			foreach ($this->urlsMatchHighlightMenu as $urlMatchHighlightMenu) {
				// Nếu URL không phải regex, hãy chuyển nó thành regex.
				if (!str_starts_with($urlMatchHighlightMenu, '/')) {
					$urlMatchHighlightMenu = '/' . $this->funcs->_regexPath($urlMatchHighlightMenu) . '/iu';
				}
				if (preg_match($urlMatchHighlightMenu, $currentRequest)) {
					add_filter('parent_file', function($parent_file) {
						return $this->parent_slug;
					});
					add_filter('submenu_file', function($submenu_file) {
						return $this->menu_slug;
					});

					/**
					 * "parent_file" và "submenu_file" chỉ có thể highlight 1 menu duy nhất.\
					 * Nếu muốn highlight nhiều menu, cần phải xử lý class="" của menu đó.
					 */
					if ($this->isSubmenuPage) {
						$this->handleAdminMenuClasses('current');
					}
					else {
						$this->handleAdminMenuClasses('wp-menu-open wp-has-current-submenu');
					}

					$this->matchedHighLightMenu();
					break;
				}
			}
		}

		/**
		 * ---
		 * Tự động khớp với request hiện tại.
		 * ---
		 * Khi truy cập submenu, highlight nó.
		 */
		else {
			if (preg_match('/' . $this->funcs->_regexPath($this->menu_slug) . '$/iu', $currentRequest)) {
				add_filter('submenu_file', function($submenu_file) {
					return $this->menu_slug;
				});
			}
		}
	}

	private function matchCurrentAccess() {
		$currentRequest = $this->request->getRequestUri();

		/**
		 * ---
		 * Tùy chọn khớp với request hiện tại.
		 * ---
		 * Xử lý "urlsMatchCurrentAccess".\
		 * Nếu có một trong các url khớp với request hiện tại,\
		 * thì chạy hàm "screenOptions", "matchedCurrentAccess".
		 */
		if (!empty($this->urlsMatchCurrentAccess) && is_array($this->urlsMatchCurrentAccess)) {
			foreach ($this->urlsMatchCurrentAccess as $urlMatchCurrentAccess) {
				// Nếu URL không phải regex, hãy chuyển nó thành regex.
				if (!str_starts_with($urlMatchCurrentAccess, '/')) {
					$urlMatchCurrentAccess = '/' . $this->funcs->_regexPath($urlMatchCurrentAccess) . '/iu';
				}
				if (preg_match($urlMatchCurrentAccess, $currentRequest)) {
					$this->matchedCurrentAccess();
					$this->screenOptions();
					break;
				}
			}
		}

		/**
		 * ---
		 * [1] Tự động khớp với request hiện tại.
		 * ---
		 * Khi $this->menu_slug khớp với request hiện tại => đang truy cập vào menu_slug này.\
		 * Chạy hàm "screenOptions" và "matchedCurrentAccess".
		 */
		else {
			if (preg_match('/' . $this->funcs->_regexPath($this->menu_slug) . '$/iu', $currentRequest)) {
				$this->matchedCurrentAccess();
				$this->screenOptions();
			}
		}
	}

	/*
	 *
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

	/*
	 *
	 */

	public function beforeInit() {}

	public function afterAddAdminPage($adminPage) {}

	public function beforeLoadAdminPage($adminPage) {}

	public function beforeInLoadAdminPage($adminPage) {}

	public function afterInLoadAdminPage($adminPage) {}

	public function afterLoadAdminPage($adminPage) {}

	public function matchedHighLightMenu() {}

	public function matchedCurrentAccess() {}

	public function afterInit() {}

	/*
	 *
	 */

	public function assets() {
		$this->styles();
		$this->scripts();
		$this->localizeScripts();
	}

	public function screenOptions() {
		/**
		 * Nếu menu hiện tại có thể hiển thị screen options.\
		 * Hãy hiển thị screen options khi truy cập.
		 */
		if ($this->showScreenOptions) {
			// Custom screen options.
			add_action('current_screen', function($screen) {
				// Ghi đè "screen id" và "screen base".
				// Mục đích để screen options hoạt động độc lập theo "screen_options_key".
				$screen->id   = $this->screenOptionsKey;
				$screen->base = $this->screenOptionsKey;

				// Truyền thêm property này vào current screen để List Table có thể gọi ra.
				$screen->show_screen_options = true;
			}, 1);

			// Save items per page option.
			add_filter('set_screen_option_' . $this->screenOptionsKey . '_items_per_page', function($default, $option, $value) {
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

	/*
	 *
	 */

	public function styles() {}

	public function scripts() {}

	public function localizeScripts() {}

}