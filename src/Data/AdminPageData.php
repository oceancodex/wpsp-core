<?php

namespace WPSPCORE\Data;

class AdminPageData {

	private $menuTitle      = null;
	private $pageTitle      = null;
	private $capability     = null;
	private $menuSlug       = null;
	private $callback       = null;
	private $iconUrl        = null;
	private $position       = null;
	private $isSubAdminPage = false;
	private $parentSlug     = null;

	public function getMenuTitle() {
		return $this->menuTitle;
	}

	public function setMenuTitle($menuTitle) {
		$this->menuTitle = $menuTitle;
	}

	public function getPageTitle() {
		return $this->pageTitle;
	}

	public function setPageTitle($pageTitle) {
		$this->pageTitle = $pageTitle;
	}

	public function getCapability() {
		return $this->capability;
	}

	public function setCapability($capability) {
		$this->capability = $capability;
	}

	public function getMenuSlug() {
		return $this->menuSlug;
	}

	public function setMenuSlug($menuSlug) {
		$this->menuSlug = $menuSlug;
	}

	public function getCallback() {
		return $this->callback;
	}

	public function setCallback($callback) {
		$this->callback = $callback;
	}

	public function getIconUrl() {
		return $this->iconUrl;
	}

	public function setIconUrl($iconUrl) {
		$this->iconUrl = $iconUrl;
	}

	public function getPosition() {
		return $this->position;
	}

	public function setPosition($position) {
		$this->position = $position;
	}

	public function getIsSubAdminPage() {
		return $this->isSubAdminPage;
	}

	public function setIsSubAdminPage($isSubAdminPage) {
		$this->isSubAdminPage = $isSubAdminPage;
	}

	public function getParentSlug() {
		return $this->parentSlug;
	}

	public function setParentSlug($parentSlug) {
		$this->parentSlug = $parentSlug;
	}

}