<?php

namespace WPSPCORE\Base;

abstract class BaseAdminPage extends BaseInstances {

	public $menu_title         = null;
	public $page_title         = null;
	public $capability         = null;
	public $menu_slug          = null;
	public $icon_url           = null;
	public $position           = null;
	public $isSubAdminPage     = false;
	public $parent_slug        = null;
	public $removeFirstSubmenu = false;

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $menu_slug = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->overrideMenuSlug($menu_slug);
		$this->customProperties();
	}

	/*
	 *
	 */

	public function overrideMenuSlug($menu_slug = null): void {
		if ($menu_slug && !$this->menu_slug) {
			$this->menu_slug = $menu_slug;
		}
	}

	/*
	 *
	 */

	public function init($path = null): void {
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

	private function addMenuPage(): string {
		return add_menu_page(
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->menu_slug,
			[$this, 'index'],
			$this->icon_url,
			$this->position
		);
	}

	private function addSubMenuPage(): string {
		return add_submenu_page(
			$this->parent_slug,
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->menu_slug,
			[$this, 'index']
		);
	}

	private function addAdminMenuPage(): void {
		add_action('admin_menu', function() {
			$adminPage = $this->isSubAdminPage ? $this->addSubMenuPage() : $this->addMenuPage();
			if ($this->removeFirstSubmenu) {
				add_filter('parent_file', function($parent_file) {
					remove_submenu_page( $this->menu_slug, $this->menu_slug);
					return $parent_file;
				});
			}
			add_action('load-' . $adminPage, function() use ($adminPage) {
				// Enqueue scripts.
				add_action('admin_enqueue_scripts', [$this, 'assets']);

				// Screen options.
				$this->screenOptions($adminPage);

				// After load this admin page.
				$this->afterLoad($adminPage);
			});
		});
		$this->afterAddAdminMenuPage();
	}

	private function highlightCurrentMenu(): void {
		$currentRequest = $this->request->getRequestUri();
		if (preg_match('/page='. $this->menu_slug. '$/', $currentRequest)) {
			add_filter('submenu_file', function($submenu_file) {
				return $this->menu_slug;
			});
		}
	}

	private function saveScreenOptions(): void {
		$itemsPerPageKey = 'set_screen_option_' . $this->funcs->_env('APP_SHORT_NAME', true) . '_' . $this->menu_slug . '_items_per_page';
		add_filter($itemsPerPageKey, function($default, $option, $value) {
			return $value;
		}, 10, 3);
	}

	/*
	 *
	 */

	public function assets(): void {
		$this->styles();
		$this->scripts();
		$this->localizeScripts();
	}

	public function screenOptions($adminPage): void {
		$screen = get_current_screen();
		if (!is_object($screen) || $screen->id != $adminPage) return;
		$args = [
			'default' => 20,
			'option'  => $this->funcs->_env('APP_SHORT_NAME', true) . '_' . $this->menu_slug . '_items_per_page',
		];
		add_screen_option('per_page', $args);
	}

	/*
	 *
	 */

	abstract public function index();

	abstract public function styles();

	abstract public function scripts();

	abstract public function localizeScripts();

	abstract public function customProperties();

	/*
	 *
	 */

	public function setMenutitle($menu_title): void {
		$this->menu_title = $menu_title;
	}

	public function setPageTitle($page_title): void {
		$this->page_title = $page_title;
	}

	public function setCapability($capability): void {
		$this->capability = $capability;
	}

	public function setMenuSlug($menu_slug): BaseAdminPage {
		$this->menu_slug = $menu_slug;
		return $this;
	}

	public function setIconUrl($icon_url): void {
		$this->icon_url = $icon_url;
	}

	public function setPosition($position): void {
		$this->position = $position;
	}

	public function setIsSubAdminPage($isSubAdminPage): void {
		$this->isSubAdminPage = $isSubAdminPage;
	}

	public function setParentSlug($parent_slug): void {
		$this->parent_slug = $parent_slug;
	}

	public function getMenuTitle(): string {
		return $this->menu_title;
	}

	public function getPageTitle(): string {
		return $this->page_title;
	}

	public function getCapability(): string {
		return $this->capability;
	}

	public function getMenuSlug(): string {
		return $this->menu_slug;
	}

	public function getIconUrl(): string {
		return $this->icon_url;
	}

	public function getPosition(): int {
		return $this->position;
	}

	public function getIsSubAdminPage(): bool {
		return $this->isSubAdminPage;
	}

	public function getParentSlug(): ?string {
		return $this->parent_slug;
	}

}