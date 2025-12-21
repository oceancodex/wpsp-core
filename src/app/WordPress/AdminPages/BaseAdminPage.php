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
	public $first_submenu_title = null;
	public $capability          = null;
	public $menu_slug           = null;
	public $icon_url            = null;
	public $position            = null;
	public $parent_slug         = null;

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
		if ($this->first_submenu_title) {
			remove_submenu_page($this->menu_slug, $this->menu_slug); // Xóa submenu tự sinh
			add_submenu_page(
				$this->menu_slug,
				$this->page_title,
				$this->first_submenu_title,
				$this->capability,
				$this->menu_slug,
				$callback
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
			$callback
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
			}, 99999999);
		}
	}

	private function matchHighlightMenu() {
		$currentRequest = $this->request->getRequestUri();

		if (preg_match('/' . preg_quote($this->menu_slug, '/') . '/', $currentRequest)
			|| preg_match('/' . preg_quote($this->menu_slug, '/') . '&updated=true$/', $currentRequest)
		) {
			add_filter('submenu_file', function($submenu_file) {
				return $this->menu_slug;
			});
		}

		if (is_array($this->urlsMatchHighlightMenu)) {
			foreach ($this->urlsMatchHighlightMenu as $urlMatchHighlightMenu) {
				$urlMatchHighlightMenu = '/' . preg_quote($urlMatchHighlightMenu, '/') . '/iu';
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
		foreach ($this->urlsMatchCurrentAccess as $url_match_current_access) {
			$url_match_current_access = '/' . $this->funcs->_regexPath($url_match_current_access) . '/iu';
			if (preg_match($url_match_current_access, $currentRequest)) {
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

	public function afterInit() {}

	public function afterAddAdminPage($adminPage) {}

	public function beforeLoadAdminPage($adminPage) {}

	public function beforeInLoadAdminPage($adminPage) {}

	public function afterInLoadAdminPage($adminPage) {}

	public function afterLoadAdminPage($adminPage) {}

	public function matchedCurrentAccess() {}

	/*
	 *
	 */

	public function assets() {
		$this->styles();
		$this->scripts();
		$this->localizeScripts();
	}

	public function screenOptions() {
		// Custom screen options panel.
		if ($this->showScreenOptions) {
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
			}, 999999999, 3);
		}
	}

	/*
	 *
	 */

	public function styles() {}

	public function scripts() {}

	public function localizeScripts() {}

}