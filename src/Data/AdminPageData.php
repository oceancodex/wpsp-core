<?php

namespace WPSPCORE\Data;

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