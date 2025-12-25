<?php

namespace WPSPCORE\App\WordPress\AdminPages;

use WPSPCORE\App\Routes\RouteTrait;
use WPSPCORE\BaseInstances;

abstract class BaseAdminPage extends BaseInstances {

	use RouteTrait;

	/**
	 * WordPress admin page properties.
	 */
	public $menu_title          = null;
	public $page_title          = null;
	public $capability          = null;
	public $menu_slug           = null;
	public $icon_url            = null;
	public $position            = null;
	public $parent_slug         = null;

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
		$this->addAdminMenuPageClasses();
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
			$callParams = $this->getCallParams($this->extraParams['path'], $this->extraParams['full_path'], $requestPath, $this, $this->callback_function);
			$callback = $this->resolveCallback($callback, $callParams);
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
			$callParams = $this->getCallParams($this->extraParams['path'], $this->extraParams['full_path'], $requestPath, $this, $this->callback_function);
			$callback = $this->resolveCallback($callback, $callParams);
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

			$this->afterAddAdminPage($adminPage);

			$this->beforeLoadAdminPage($adminPage);

			add_action('load-' . $adminPage, function() use ($adminPage) {
				$this->beforeInLoadAdminPage($adminPage);

				// Enqueue scripts.
				add_action('admin_enqueue_scripts', [$this, 'assets']);

				$this->afterInLoadAdminPage($adminPage);
			});

			$this->afterLoadAdminPage($adminPage);
		});

		if ($this->removeFirstSubmenu) {
			add_action('admin_menu', function() {
				remove_submenu_page($this->menu_slug, $this->menu_slug);
			}, 9999999999);
		}
	}

	private function addAdminMenuPageClasses() {
		if ($this->classes) {
			if ($this->isSubmenuPage) {
				add_action('admin_menu', function () {
					global $submenu;

					if (!isset($submenu[$this->parent_slug])) {
						return;
					}

					foreach ($submenu[$this->parent_slug] as $index => &$item) {
						if ($item[2] === $this->menu_slug) {
							if (isset($item[4])) {
								$item[4] .= ' ' . $this->classes;
							}
							else {
								$item[4] = $this->classes;
							}
						}
					}
				}, 9999999999);
			}
			else {
				add_action('admin_menu', function () {
					global $menu;

					foreach ($menu as $index => &$item) {
						if ($item[2] === $this->menu_slug) {
							if (isset($item[4])) {
								$item[4] .= ' ' . $this->classes;
							}
							else {
								$item[4] = $this->classes;
							}
							break;
						}
					}
				}, 9999999999);
			}
		}
		if ($this->firstSubmenuClasses) {
			add_action('admin_menu', function () {
				global $submenu;

				if (!isset($submenu[$this->menu_slug])) {
					return;
				}

				foreach ($submenu[$this->menu_slug] as $index => &$item) {
					if ($item[2] === $this->menu_slug) {
						if (isset($item[4])) {
							$item[4] .= ' ' . $this->firstSubmenuClasses;
						}
						else {
							$item[4] = $this->firstSubmenuClasses;
						}
					}
				}
			}, 9999999999);
		}
	}

	private function matchHighlightMenu() {
		$currentRequest = $this->request->getRequestUri();

		/**
		 * Khi truy cập submenu, highlight nó.
		 */
		if (preg_match('/' . $this->funcs->_regexPath($this->menu_slug) . '/', $currentRequest)
			|| preg_match('/' . $this->funcs->_regexPath($this->menu_slug) . '&updated=true$/', $currentRequest)
		) {
			add_filter('submenu_file', function($submenu_file) {
				return $this->menu_slug;
			});
		}

		/**
		 * Xử lý "urlsMatchHighlightMenu".\
		 * Nếu có một trong các url khớp với request hiện tại,\
		 * thì highlight submenu nơi khai báo "urlsMatchHighlightMenu".
		 */
		if (is_array($this->urlsMatchHighlightMenu)) {
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
					break;
				}
			}
		}
	}

	private function matchCurrentAccess() {
		$currentRequest = $this->request->getRequestUri();

		/**
		 * Khi menu_slug khớp với request hiện tại.\
		 * Nhận định đang truy cập vào menu_slug này.\
		 * Chạy hàm "currentScreen" và "screenOptions".
		 */
		if (preg_match('/' . $this->funcs->_regexPath($this->menu_slug) . '$/iu', $currentRequest)) {
			/**
			 * Cần chạy hàm "currentScreen" tại đây.\
			 * Vì đôi khi muốn khởi tạo Custom List Table mà không hiển thị screen options panel.
			 */
			add_action('current_screen', function($screen) {
				$this->currentScreen($screen);
			});
			$this->screenOptions();
		}

		/**
		 * Xử lý "urlsMatchCurrentAccess".\
		 * Nếu có một trong các url khớp với request hiện tại,\
		 * thì chạy hàm "currentScreen", "screenOptions" và "matchedCurrentAccess".
		 */
		foreach ($this->urlsMatchCurrentAccess as $urlMatchCurrentAccess) {
			// Nếu URL không phải regex, hãy chuyển nó thành regex.
			if (!str_starts_with($urlMatchCurrentAccess, '/')) {
				$urlMatchCurrentAccess = '/' . $this->funcs->_regexPath($urlMatchCurrentAccess) . '/iu';
			}
			if (preg_match($urlMatchCurrentAccess, $currentRequest)) {
				/**
				 * Cần chạy hàm "currentScreen" tại đây.\
				 * Vì đôi khi muốn khởi tạo Custom List Table mà không hiển thị screen options panel.
				 */
				add_action('current_screen', function($screen) {
					$this->currentScreen($screen);
				});
				$this->screenOptions();
				$this->matchedCurrentAccess();
				break;
			}
		}
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

	public function currentScreen($screen) {}

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