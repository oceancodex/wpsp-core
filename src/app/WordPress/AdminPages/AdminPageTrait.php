<?php
namespace WPSPCORE\App\WordPress\AdminPages;

trait AdminPageTrait {
	public $override_page_title = null;

	/**
	 * Ghi đè page_title.
	 */
	public function overridePageTitle($overrideTitle = null) {
		$overrideTitle = $overrideTitle ?? $this->override_page_title ?? $this->page_title;
		if ($overrideTitle) {
			add_filter('admin_title', function($admin_title, $title) use ($overrideTitle) {
				return $overrideTitle;
			}, 9999999999, 2);
		}
	}

	/**
	 * Gọi phương thức trong admin page class nếu tồn tại.\
	 * Trước hết cần prepare method để DI.\
	 * Sau đó gọi phương thức với DI.
	 */
	public function callAdminPageMethod($method) {
		if (method_exists($this, $method)) {
			$callback = $this->prepareCallbackFunction($method, $this->menu_slug, $this->menu_slug);
			$this->resolveAndCall($callback);
		}
	}

	/**
	 * Thêm meta box vào admin page.\
	 * Sau đó render meta box trong blade bằng:
	 * @adminpagemetabox(string $id, string $admin_page_menu_class, array $admin_page_metabox_args)
	 * @endadminpagemetabox
	 */
	public function addAdminPageMetaBox($id, $content) {
		$this->adminPageMetaBoxes[$id] = $content;
	}

}