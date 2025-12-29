<?php
namespace WPSPCORE\App\WordPress\AdminPages;

trait AdminPageTrait {
	public $override_page_title = null;

	public function overridePageTitle($overrideTitle = null) {
		$overrideTitle = $overrideTitle ?? $this->override_page_title ?? $this->page_title;
		if ($overrideTitle) {
			add_filter('admin_title', function($admin_title, $title) use ($overrideTitle) {
				return $overrideTitle;
			}, 9999999999, 2);
		}
	}

}