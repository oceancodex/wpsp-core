<?php

namespace WPSPCORE\Base;

use Illuminate\Http\Request;

abstract class BaseAdminPage extends BaseInstances {

	public $menu_title                  = null;
	public $page_title                  = null;
	public $first_submenu_title         = null;
	public $capability                  = null;
	public $menu_slug                   = null;
	public $icon_url                    = null;
	public $position                    = null;
	public $parent_slug                 = null;
	public $is_submenu_page             = false;
	public $remove_first_submenu        = false;
	public $urls_highlight_current_menu = null;
	public $callback_function           = null;

	protected $screen_options           = false;
	protected $screen_options_key       = null;

	public function afterConstruct() {
		$this->callback_function  = $this->extraParams['callback_function'];
		$this->screen_options_key = $this->screen_options_key ?: $this->funcs->_slugParams(['page']) ?? $this->menu_slug;
		$this->overrideMenuSlug($this->extraParams['path']);
		$this->customProperties();
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
		$this->saveScreenOptions();
		$this->highlightCurrentMenu();
		$this->afterInit();
	}

	/*
	 *
	 */

	public function beforeInit() {}

	public function afterInit() {}

	public function afterAddAdminMenuPage() {}

	public function afterLoad($adminPage) {}

	/*
	 *
	 */

	private function addMenuPage() {
		$callback = null;
		if ($this->callback_function && method_exists($this, $this->callback_function)) {
			$callback = function() {
				$container = $this->funcs->getApplication() ?? (\Illuminate\Foundation\Application::getInstance() ?? null);
				if (!$container) {
					// fallback bình thường nếu không có container
					return $this->{$this->callback_function}();
				}
				// Dùng container->call() để auto inject dependencies
				return $container->call([$this, $this->callback_function]);
			};
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
			$callback = function() {
				$container = $this->funcs->getApplication() ?? (\Illuminate\Foundation\Application::getInstance() ?? null);
				if (!$container) {
					return $this->{$this->callback_function}();
				}
				return $container->call([$this, $this->callback_function]);
			};
		}
		return add_submenu_page(
			$this->parent_slug,
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->menu_slug,
			$callback
		);
	}

	private function addAdminMenuPage() {
		add_action('admin_menu', function() {
			$adminPage = $this->is_submenu_page ? $this->addSubMenuPage() : $this->addMenuPage();
			$this->afterAddAdminMenuPage();
			add_action('load-' . $adminPage, function() use ($adminPage) {
				// Enqueue scripts.
				add_action('admin_enqueue_scripts', [$this, 'assets']);

				// Screen options.
				if ($this->screen_options) $this->screenOptions($adminPage);

				// After load this admin page.
				$this->afterLoad($adminPage);
			});
		});

		if ($this->remove_first_submenu) {
			add_action('admin_menu', function() {
				remove_submenu_page($this->menu_slug, $this->menu_slug);
			}, 99999999);
		}
	}

	private function highlightCurrentMenu() {
		$currentRequest = $this->request->getRequestUri();
		if (preg_match('/' . preg_quote($this->menu_slug, '/') . '$|' . preg_quote($this->menu_slug, '/') . '&updated=true$/', $currentRequest)) {
			add_filter('submenu_file', function($submenu_file) {
				return $this->menu_slug;
			});
		}
		if (is_array($this->urls_highlight_current_menu)) {
			foreach ($this->urls_highlight_current_menu as $url_highlight_current_menu) {
				$url_highlight_current_menu = '/' . preg_quote($url_highlight_current_menu, '/') . '/iu';
				if (preg_match($url_highlight_current_menu, $currentRequest)) {
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

	private function saveScreenOptions() {
		$itemsPerPageKey = 'set_screen_option_' . $this->screen_options_key . '_items_per_page';
		add_filter($itemsPerPageKey, function($default, $option, $value) {
			return $value;
		}, 10, 3);
	}

	/*
	 *
	 */

	public function assets() {
		$this->styles();
		$this->scripts();
		$this->localizeScripts();
	}

	public function screenOptions($adminPage) {
		$screen = get_current_screen();
		if (!is_object($screen) || $screen->id != $adminPage) return;
		$args = [
			'default' => 20,
			'option'  => $this->screen_options_key . '_items_per_page',
		];
		add_screen_option('per_page', $args);
	}

	/*
	 *
	 */

	public function styles() {}

	public function scripts() {}

	public function localizeScripts() {}

	public function customProperties() {}

	/*
	 *
	 */

	public function setMenutitle($menu_title) {
		$this->menu_title = $menu_title;
	}

	public function setPageTitle($page_title) {
		$this->page_title = $page_title;
	}

	public function setCapability($capability) {
		$this->capability = $capability;
	}

	public function setMenuSlug($menu_slug) {
		$this->menu_slug = $menu_slug;
		return $this;
	}

	public function setIconUrl($icon_url) {
		$this->icon_url = $icon_url;
	}

	public function setPosition($position) {
		$this->position = $position;
	}

	public function setIsSubAdminPage($is_submenu_page) {
		$this->is_submenu_page = $is_submenu_page;
	}

	public function setParentSlug($parent_slug) {
		$this->parent_slug = $parent_slug;
	}

	public function getMenuTitle() {
		return $this->menu_title;
	}

	public function getPageTitle() {
		return $this->page_title;
	}

	public function getCapability() {
		return $this->capability;
	}

	public function getMenuSlug() {
		return $this->menu_slug;
	}

	public function getIconUrl() {
		return $this->icon_url;
	}

	public function getPosition() {
		return $this->position;
	}

	public function getIsSubAdminPage() {
		return $this->is_submenu_page;
	}

	public function getParentSlug() {
		return $this->parent_slug;
	}

}