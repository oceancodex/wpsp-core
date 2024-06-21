<?php

namespace OCBPCORE\Objects\Data;

class AdminPageData {

	private mixed $menuTitle      = null;
	private mixed $pageTitle      = null;
	private mixed $capability     = null;
	private mixed $menuSlug       = null;
	private mixed $callback       = null;
	private mixed $iconUrl        = null;
	private mixed $position       = null;
	private mixed $isSubAdminPage = false;
	private mixed $parentSlug     = null;

	/*
	 *
	 */

	public function __construct($adminPage) {
		$this->menuTitle      = $adminPage->menuTitle;
		$this->pageTitle      = $adminPage->pageTitle;
		$this->capability     = $adminPage->capability;
		$this->menuSlug       = $adminPage->menuSlug;
		$this->callback       = [$adminPage, 'index'];
		$this->iconUrl        = $adminPage->iconUrl;
		$this->position       = $adminPage->position;
		$this->isSubAdminPage = $adminPage->isSubAdminPage;
		$this->parentSlug     = $adminPage->parentSlug;
	}

	/*
	 * ADDERS
	 */

	public function addAdminPage($enqueueScripts = null): void {
		$menuPage = $this->isSubAdminPage ? $this->addSubMenuPage() : $this->addMenuPage();
		if ($enqueueScripts) {
			add_action('load-' . $menuPage, function() use ($enqueueScripts) {
				add_action('admin_enqueue_scripts', function() use ($enqueueScripts) {
					call_user_func($enqueueScripts);
				});
			});
		}
	}

	public function addMenuPage(): string {
		return add_menu_page(
			$this->pageTitle,
			$this->menuTitle,
			$this->capability,
			$this->menuSlug,
			$this->callback,
			$this->iconUrl,
			$this->position
		);
	}

	public function addSubMenuPage(): string {
		return add_submenu_page(
			$this->parentSlug,
			$this->pageTitle,
			$this->menuTitle,
			$this->capability,
			$this->menuSlug,
			$this->callback
		);
	}

	/*
	 * GETTERS & SETTERS
	 */

	public function getMenuTitle(): string {
		return $this->menuTitle;
	}

	public function setMenuTitle($menuTitle): void {
		$this->menuTitle = $menuTitle;
	}

	public function getPageTitle(): string {
		return $this->pageTitle;
	}

	public function setPageTitle($pageTitle): void {
		$this->pageTitle = $pageTitle;
	}

	public function getCapability(): string {
		return $this->capability;
	}

	public function setCapability($capability): void {
		$this->capability = $capability;
	}

	public function getMenuSlug(): string {
		return $this->menuSlug;
	}

	public function setMenuSlug($menuSlug): void {
		$this->menuSlug = $menuSlug;
	}

	public function getCallback() {
		return $this->callback;
	}

	public function setCallback($callback): void {
		$this->callback = $callback;
	}

	public function getIconUrl(): string {
		return $this->iconUrl;
	}

	public function setIconUrl($iconUrl): void {
		$this->iconUrl = $iconUrl;
	}

	public function getPosition(): int {
		return $this->position;
	}

	public function setPosition($position): void {
		$this->position = $position;
	}

	public function getIsSubAdminPage(): bool {
		return $this->isSubAdminPage;
	}

	public function setIsSubAdminPage($isSubAdminPage): void {
		$this->isSubAdminPage = $isSubAdminPage;
	}

	public function getParentSlug(): string {
		return $this->parentSlug;
	}

	public function setParentSlug($parentSlug): void {
		$this->parentSlug = $parentSlug;
	}

}