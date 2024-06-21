<?php

namespace WPSPCORE\Base;

use WPSPCORE\Objects\Data\AdminPageData;
use WPSPCORE\Objects\Http\HttpFoundation;

abstract class BaseAdminPage extends HttpFoundation {

	public mixed $menuTitle      = null;
	public mixed $pageTitle      = null;
	public mixed $capability     = null;
	public mixed $menuSlug       = null;
	public mixed $iconUrl        = null;
	public mixed $position       = null;
	public mixed $isSubAdminPage = false;
	public mixed $parentSlug     = null;

	public function __construct($menuSlug = null) {
		parent::__construct();
		$this->overrideMenuSlug($menuSlug);
		$this->customProperties();
	}

	/*
	 *
	 */

	public function init($path = null): void {
		add_action('admin_menu', function() {
			(new AdminPageData($this))->addAdminPage($this->assets());
		});
	}

	public function overrideMenuSlug($menuSlug = null): void {
		if ($menuSlug && !$this->menuSlug) {
			$this->menuSlug = $menuSlug;
		}
	}

	public function assets(): \Closure {
		return function() {
			$this->styles();
			$this->scripts();
			$this->localizeScripts();
		};
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