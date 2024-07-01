<?php

namespace WPSPCORE\Base;

use WPSPCORE\Data\AdminPageData;

abstract class BaseAdminPage extends BaseInstances {

	public mixed $menuTitle      = null;
	public mixed $pageTitle      = null;
	public mixed $capability     = null;
	public mixed $menuSlug       = null;
	public mixed $iconUrl        = null;
	public mixed $position       = null;
	public mixed $isSubAdminPage = false;
	public mixed $parentSlug     = null;

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $menuSlug = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->overrideMenuSlug($menuSlug);
		$this->customProperties();
	}

	/*
	 *
	 */

	public function init($path = null): void {
		// Add admin page.
		add_action('admin_menu', function () {
			$menuPage = $this->isSubAdminPage ? $this->addSubMenuPage() : $this->addMenuPage();
			add_action('load-' . $menuPage, function () use ($menuPage) {

				// Enqueue scripts.
				add_action('admin_enqueue_scripts', [$this, 'assets']);

				// Screen options.
				$this->screenOptions($menuPage);
			});
		});

		// Save screen options.
		add_filter('set_screen_option_items_per_page', function ($default, $option, $value) {
			return $value;
		}, 10, 3);
	}

	public function overrideMenuSlug($menuSlug = null): void {
		if ($menuSlug && !$this->menuSlug) {
			$this->menuSlug = $menuSlug;
		}
	}

	public function assets(): \Closure {
		return function () {
			$this->styles();
			$this->scripts();
			$this->localizeScripts();
		};
	}

	/*
	 *
	 */

	private function addMenuPage(): string {
		return add_menu_page(
			$this->pageTitle,
			$this->menuTitle,
			$this->capability,
			$this->menuSlug,
			[$this, 'index'],
			$this->iconUrl,
			$this->position
		);
	}

	private function addSubMenuPage(): string {
		return add_submenu_page(
			$this->parentSlug,
			$this->pageTitle,
			$this->menuTitle,
			$this->capability,
			$this->menuSlug,
			[$this, 'index']
		);
	}

	protected function screenOptions($menuPage): void {
		$screen = get_current_screen();
		if (!is_object($screen) || $screen->id != $menuPage) return;
		$args = [
			'default' => 20,
			'option'  => 'items_per_page',
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

	public function setMenutitle($menuTitle): void {
		$this->menuTitle = $menuTitle;
	}

	public function setPageTitle($pageTitle): void {
		$this->pageTitle = $pageTitle;
	}

	public function setCapability($capability): void {
		$this->capability = $capability;
	}

	public function setMenuSlug($menuSlug): BaseAdminPage {
		$this->menuSlug = $menuSlug;
		return $this;
	}

	public function setIconUrl($iconUrl): void {
		$this->iconUrl = $iconUrl;
	}

	public function setPosition($position): void {
		$this->position = $position;
	}

	public function setIsSubAdminPage($isSubAdminPage): void {
		$this->isSubAdminPage = $isSubAdminPage;
	}

	public function setParentSlug($parentSlug): void {
		$this->parentSlug = $parentSlug;
	}

	public function getMenuTitle(): string {
		return $this->menuTitle;
	}

	public function getPageTitle(): string {
		return $this->pageTitle;
	}

	public function getCapability(): string {
		return $this->capability;
	}

	public function getMenuSlug(): string {
		return $this->menuSlug;
	}

	public function getIconUrl(): string {
		return $this->iconUrl;
	}

	public function getPosition(): int {
		return $this->position;
	}

	public function getIsSubAdminPage(): bool {
		return $this->isSubAdminPage;
	}

	public function getParentSlug(): ?string {
		return $this->parentSlug;
	}

}