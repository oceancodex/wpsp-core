<?php

namespace WPSPCORE\App\WordPress\AdminPages;

use WPSPCORE\App\Traits\InstanceMiddlewareTrait;
use WPSPCORE\BaseInstances;

abstract class BaseAdminPage extends BaseInstances {

	use AdminPageTrait, AdminPageScreenOptionsTrait, AdminPageMetaBoxesTrait, InstanceMiddlewareTrait;

	/**
	 * WordPress admin page properties.
	 */
	public $menu_title             = '';
	public $page_title             = '';
	public $capability             = null;
	public $menu_slug              = null;
	public $icon_url               = null;
	public $position               = null;
	public $parent_slug            = '';
	public $priority               = 10;

	/**
	 * Custom properties.
	 */
	public $forceInit              = false;
	public $forceInitPriority      = 10;
	public $forceInitIsSubmenuPage = false;
	public $forceInitParentSlug    = '';
	public $forceInitPageTitle     = '';
	public $forceInitMenuTitle     = '';
	public $forceInitCapability    = null;
	public $forceInitSlug          = null;
	public $forceInitCallback      = null;
	public $forceInitIconUrl       = null;
	public $forceInitPosition      = null;

	public $classes                = null;
	public $isSubmenuPage          = false;
	public $firstSubmenuTitle      = '';
	public $firstSubmenuClasses    = null;
	public $removeFirstSubmenu     = false;

	public $urlsMatchCurrentAccess = [];
	public $urlsMatchHighlightMenu = [];

	public $callback_function      = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideMenuSlug($this->extraParams['full_path'] ?? null);
		$this->overridePriority($this->extraParams['priority'] ?? null);

		$this->forceInit();

		if (!$this->screenId) {
			$this->screenId = $this->funcs->_slugParams(['page']) ?? $this->menu_slug;
		}
	}

	/*
	 *
	 */

	/**
	 * Cần phải override "callback_function" để xử lý vấn đề maybeCallIndexMethod().\
	 * Tình huống xảy ra với AdminPages/wpsp_tab_tools.php\
	 * Khi callback của metabox là index(). Thì index() sẽ được gọi 2 lần.\
	 * Một lần ở maybeCallIndexMethod() và một lần ở callback của metabox.\
	 * Vì vậy, nếu chỉ muốn index() là callback hợp lệ render ra nội dung của metabox thì cần cho $callback_function = false.
	 */
	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overrideMenuSlug($menu_slug = null) {
		if ($menu_slug && !$this->menu_slug) {
			$this->menu_slug = $menu_slug;
		}
	}

	private function overridePriority($priority = null) {
		if ($priority && !$this->priority) {
			$this->priority = $priority;
		}
	}

	/*
	 *
	 */

	public function init() {
		if (method_exists($this, 'beforeInit'))  {
			$this->callAdminPageMethod('beforeInit');
		}

		$this->addAdminMenuPage();
		$this->handleAdminMenuClasses();
		$this->matchHighlightMenu();
		$this->matchCurrentAccess();

		if (method_exists($this, 'afterInit'))  {
			$this->callAdminPageMethod('afterInit');
		}
	}

	public function forceInit() {
		// Set force init (boolean).
		if (!$this->forceInit && isset($this->extraParams['route']->args['force_init']) && $this->extraParams['route']->args['force_init'] === true) {
			$this->forceInit = true;
//			$routeMethod = $this->extraParams['route']?->method ?? null;
		}

		// Set force init priority.
		if (!$this->forceInitPriority && isset($this->extraParams['route']->args['force_init_priority'])) {
			$this->forceInitPriority = $this->extraParams['route']->args['force_init_priority'];
		}

		// Set force init is submenu page.
		if (!$this->forceInitIsSubmenuPage && isset($this->extraParams['route']?->args['force_init_is_submenu_page'])) {
			$this->forceInitIsSubmenuPage = $this->extraParams['route']?->args['force_init_is_submenu_page'];
		}

		// Set force init parent slug.
		if (!$this->forceInitParentSlug && isset($this->extraParams['route']?->args['force_init_parent_slug'])) {
			$this->forceInitParentSlug = $this->extraParams['route']?->args['force_init_parent_slug'];
		}

		// Set force init page title.
		if (!$this->forceInitPageTitle && isset($this->extraParams['route']?->args['force_init_page_title'])) {
			$this->forceInitPageTitle = $this->extraParams['route']?->args['force_init_page_title'];
		}

		// Set force init menu title.
		if (!$this->forceInitMenuTitle && isset($this->extraParams['route']?->args['force_init_menu_title'])) {
			$this->forceInitMenuTitle = $this->extraParams['route']?->args['force_init_menu_title'];
		}

		// Set force init capability.
		if (!$this->forceInitCapability && isset($this->extraParams['route']?->args['force_init_capability'])) {
			$this->forceInitCapability = $this->extraParams['route']?->args['force_init_capability'];
		}

		// Set force init slug.
		if (!$this->forceInitSlug && isset($this->extraParams['route']?->args['force_init_slug'])) {
			$this->forceInitSlug = $this->extraParams['route']?->args['force_init_slug'];
		}

		// Set force init callback.
		if (!$this->forceInitCallback && isset($this->extraParams['route']?->args['force_init_callback'])) {
			$this->forceInitCallback = $this->extraParams['route']?->args['force_init_callback'];
		}

		// Set force init icon url.
		if (!$this->forceInitIconUrl && isset($this->extraParams['route']?->args['force_init_icon_url'])) {
			$this->forceInitIconUrl = $this->extraParams['route']?->args['force_init_icon_url'];
		}

		// Set force init position.
		if (!$this->forceInitPosition && isset($this->extraParams['route']?->args['force_init_position'])) {
			$this->forceInitPosition = $this->extraParams['route']?->args['force_init_position'];
		}

		if ($this->forceInit) {
			if (method_exists($this, 'beforeForceInit'))  {
				$this->callAdminPageMethod('beforeForceInit');
			}

			$this->addAdminMenuPageForce();

			if (method_exists($this, 'afterForceInit'))  {
				$this->callAdminPageMethod('afterForceInit');
			}
		}
	}

	/*
	 *
	 */

	private function addMenuPage() {
		$callback = null;

		if ($this->callback_function && method_exists($this, $this->callback_function)) {
			$callback = $this->prepareCallbackFunction($this->callback_function, $this->menu_slug, $this->extraParams['full_path'] ?? $this->menu_slug);
		}

		// Nếu force_init là true và không có callback_function, thì sử dụng phương thức index
		if ($this->forceInit && $this->callback_function === null) {
			$callback = $this->prepareCallbackFunction('index', $this->menu_slug, $this->extraParams['full_path'] ?? $this->menu_slug);
		}

		$menuPage = add_menu_page(
			$this->page_title ?: $this->menu_title ?: $this->menu_slug,
			$this->menu_title ?: $this->menu_slug,
			$this->capability,
			$this->menu_slug,
			$callback,
			$this->icon_url,
			$this->position
		);

		// Khi có nhiều submenu, WordPress sẽ tự sinh submenu cho trang chính.
		// Thay đổi tên submenu tự sinh.
		if ($this->firstSubmenuTitle) {
			remove_submenu_page($this->menu_slug, $this->menu_slug); // Xóa submenu tự sinh
			add_submenu_page(
				$this->menu_slug,
				$this->page_title ?: $this->firstSubmenuTitle,
				$this->firstSubmenuTitle,
				$this->capability,
				$this->forceInit ? ($this->forceInitSlug ?? $this->menu_slug) : $this->menu_slug,
				$callback,
				$this->position
			);
		}

		return $menuPage;
	}

	private function addSubMenuPage() {
		$callback = null;

		if ($this->callback_function && method_exists($this, $this->callback_function)) {
			$callback = $this->prepareCallbackFunction($this->callback_function, $this->menu_slug, $this->extraParams['full_path'] ?? $this->menu_slug);
		}

		// Nếu force_init là true và không có callback_function, thì sử dụng phương thức index
		if ($this->forceInit && $this->callback_function === null) {
			$callback = $this->prepareCallbackFunction('index', $this->menu_slug, $this->extraParams['full_path'] ?? $this->menu_slug);
		}

		$subMenuPage = add_submenu_page(
			$this->parent_slug,
			$this->page_title ?: $this->menu_title ?: $this->menu_slug,
			$this->menu_title ?: $this->menu_slug,
			$this->capability,
			$this->menu_slug,
			$callback,
			$this->position
		);

		/**
		 * Khi đăng ký và load submenu page và không có parent_slug, thiết lập title cho trang admin trước khi header render.\
		 * Hook load-{$hook} chạy trong admin.php trước khi include admin-header.php, nên $title đã có giá trị khi tới strip_tags. Cách này cũng giúp tab trình duyệt hiện đúng tiêu đề.
		 */
		if ($subMenuPage && empty($this->parent_slug)) {
			$pageTitle = $this->page_title ?: $this->menu_title ?: $this->menu_slug;
			add_action('load-' . $subMenuPage, function () use ($pageTitle) {
				global $title;
				if (empty($title)) {
					$title = $pageTitle;
				}
			});
		}

		return $subMenuPage;
	}

	private function addAdminMenuPage() {
		add_action('admin_menu', function() {
			$adminPage = $this->isSubmenuPage ? $this->addSubMenuPage() : $this->addMenuPage();

			// Hook sau khi add admin menu page hoặc submenu page.
			if (method_exists($this, 'afterAddAdminPage')) {
				$this->callAdminPageMethod('afterAddAdminPage', ['adminPage' => $adminPage]);
			}

			// Hook chạy trước khi load admin page.
			if (method_exists($this, 'beforeLoadAdminPage')) {
				$this->callAdminPageMethod('beforeLoadAdminPage', ['adminPage' => $adminPage]);
			}

			/**
			 * Action "load-{admin_page}" chỉ hoạt động với admin menu page được register với slug chuẩn WordPress. Ví dụ: "edit.php", "post-new.php", hoặc "my_custom_page".\
			 * Với các dạng slug khác như: "wpsp&tab=tab-1", action này không hoạt động.
			 */
			add_action('load-' . $adminPage, function() use ($adminPage) {
				if (method_exists($this, 'beforeInLoadAdminPage')) {
					$this->callAdminPageMethod('beforeInLoadAdminPage', ['adminPage' => $adminPage]);
				}

				// Enqueue assets.
				$this->assets();

				// Hook chạy trong khi load admin page.
				if (method_exists($this, 'afterInLoadAdminPage')) {
					$this->callAdminPageMethod('afterInLoadAdminPage', ['adminPage' => $adminPage]);
				}
			});

			// Hook chạy sau khi load admin page.
			if (method_exists($this, 'afterLoadAdminPage')) {
				$this->callAdminPageMethod('afterLoadAdminPage', ['adminPage' => $adminPage]);
			}
		}, $this->priority, $this->extraParams['accepted_args'] ?? 1);

		/**
		 * Khi menu có nhiều submenu, WordPress sẽ tự sinh submenu cho trang chính ở vị trí đầu tiên.\
		 * Loại bỏ submenu tự sinh này khỏi danh sách menu items.
		 */
		if ($this->removeFirstSubmenu) {
			add_action('admin_menu', function() {
				remove_submenu_page($this->menu_slug, $this->menu_slug);
			}, 9999999999);
		}
	}

	/*
	 *
	 */

	private function addMenuPageForce() {
		$callback = null;

		if ($this->forceInitCallback && method_exists($this, $this->forceInitCallback)) {
			$callback = $this->prepareCallbackFunction($this->forceInitCallback, $this->forceInitSlug, $this->forceInitSlug);
		}

		// Nếu force_init là true và không có callback_function, thì sử dụng phương thức index
		if ($this->forceInit && $this->forceInitCallback === null) {
			$callback = $this->prepareCallbackFunction('index', $this->forceInitSlug, $this->forceInitSlug);
		}

		$menuPage = add_menu_page(
			$this->forceInitPageTitle ?: $this->forceInitMenuTitle ?: $this->forceInitSlug,
			$this->forceInitMenuTitle ?: $this->forceInitSlug,
			$this->forceInitCapability,
			$this->forceInitSlug,
			$callback,
			$this->forceInitIconUrl,
			$this->forceInitPosition
		);

		return $menuPage;
	}

	private function addSubMenuPageForce() {
		$callback = null;

		if ($this->forceInitCallback && method_exists($this, $this->forceInitCallback)) {
			$callback = $this->prepareCallbackFunction($this->forceInitCallback, $this->forceInitSlug, $this->forceInitSlug);
		}

		// Nếu force_init là true và không có callback_function, thì sử dụng phương thức index
		if ($this->forceInit && $this->forceInitCallback === null) {
			$callback = $this->prepareCallbackFunction('index', $this->forceInitSlug, $this->forceInitSlug);
		}

		$subMenuPage = add_submenu_page(
			$this->forceInitParentSlug,
			$this->forceInitPageTitle ?: $this->forceInitMenuTitle ?: $this->forceInitSlug,
			$this->forceInitMenuTitle ?: $this->forceInitSlug,
			$this->forceInitCapability,
			$this->forceInitSlug,
			$callback,
			$this->forceInitPosition
		);

		/**
		 * Khi đăng ký và load submenu page và không có parent_slug, thiết lập title cho trang admin trước khi header render.\
		 * Hook load-{$hook} chạy trong admin.php trước khi include admin-header.php, nên $title đã có giá trị khi tới strip_tags. Cách này cũng giúp tab trình duyệt hiện đúng tiêu đề.
		 */
		if ($subMenuPage && empty($this->forceInitParentSlug)) {
			$pageTitle = $this->forceInitPageTitle ?: $this->forceInitMenuTitle ?: $this->forceInitSlug;
			add_action('load-' . $subMenuPage, function () use ($pageTitle) {
				global $title;
				if (empty($title)) {
					$title = $pageTitle;
				}
			});
		}

		return $subMenuPage;
	}

	private function addAdminMenuPageForce() {
		add_action('admin_menu', function() {
			$adminPage = $this->forceInitIsSubmenuPage ? $this->addSubMenuPageForce() : $this->addMenuPageForce();

			// Hook sau khi add admin menu page hoặc submenu page.
			if (method_exists($this, 'afterAddAdminPageForce')) {
				$this->callAdminPageMethod('afterAddAdminPageForce', ['adminPage' => $adminPage]);
			}

			// Hook chạy trước khi load admin page.
			if (method_exists($this, 'beforeLoadAdminPageForce')) {
				$this->callAdminPageMethod('beforeLoadAdminPageForce', ['adminPage' => $adminPage]);
			}

			/**
			 * Action "load-{admin_page}" chỉ hoạt động với admin menu page được register với slug chuẩn WordPress. Ví dụ: "edit.php", "post-new.php", hoặc "my_custom_page".\
			 * Với các dạng slug khác như: "wpsp&tab=tab-1", action này không hoạt động.
			 */
			add_action('load-' . $adminPage, function() use ($adminPage) {
				if (method_exists($this, 'beforeInLoadAdminPageForce')) {
					$this->callAdminPageMethod('beforeInLoadAdminPageForce', ['adminPage' => $adminPage]);
				}

				// Enqueue assets.
				$this->assets();

				// Hook chạy trong khi load admin page.
				if (method_exists($this, 'afterInLoadAdminPageForce')) {
					$this->callAdminPageMethod('afterInLoadAdminPageForce', ['adminPage' => $adminPage]);
				}
			});

			// Hook chạy sau khi load admin page.
			if (method_exists($this, 'afterLoadAdminPageForce')) {
				$this->callAdminPageMethod('afterLoadAdminPageForce', ['adminPage' => $adminPage]);
			}
		}, $this->forceInitPriority);
	}

	/*
	 *
	 */

	/**
	 * Mặc định khi "add_menu_page" hay "add_submenu_page" với slug tiêu chuẩn và callback "index"\
	 * thì callback sẽ được gọi bình thường. Tuy nhiên, khi thêm admin menu page mà slug chứa dấu "&" thì callback sẽ không được gọi.
	 * Hàm này sẽ gọi method "index" để thực thi callback theo đúng format bình thường.
	 */
	private function maybeCallIndexMethod() {
		if (
			!$this->forceInit
			&& $this->callback_function === 'index'
			&& method_exists($this, 'index')
			&& str_contains($this->menu_slug, '&')
		) {
			$this->callAdminPageMethod('index');
		}
	}

	private function matchHighlightMenu() {
		$currentRequest = $this->request->getRequestUri();

		/**
		 * ---
		 * Tùy chọn khớp với request hiện tại.
		 * ---
		 * Xử lý "urlsMatchHighlightMenu".\
		 * Nếu có một trong các url khớp với request hiện tại,\
		 * thì highlight submenu nơi khai báo "urlsMatchHighlightMenu".
		 */
		if (!empty($this->urlsMatchHighlightMenu) && is_array($this->urlsMatchHighlightMenu)) {
			foreach ($this->urlsMatchHighlightMenu as $urlMatchHighlightMenu) {
				// Nếu URL không phải regex, hãy chuyển nó thành regex.
				if (!str_starts_with($urlMatchHighlightMenu, '/')) {
					$urlMatchHighlightMenu = '/' . $this->funcs->_regexPath($urlMatchHighlightMenu) . '/iu';
				}
				if (@preg_match($urlMatchHighlightMenu, $currentRequest)) {
					add_filter('parent_file', function($parent_file) {
						return $this->parent_slug;
					});
					add_filter('submenu_file', function($submenu_file) {
						return $this->menu_slug;
					});

					/**
					 * "parent_file" và "submenu_file" chỉ có thể highlight 1 menu duy nhất.\
					 * Nếu muốn highlight nhiều menu, cần phải xử lý class="" của menu đó.
					 */
					if ($this->isSubmenuPage) {
						$this->handleAdminMenuClasses('current');
					}
					else {
						$this->handleAdminMenuClasses('wp-menu-open wp-has-current-submenu');
					}

					if (method_exists($this, 'matchedHighLightMenu')) {
						$this->callAdminPageMethod('matchedHighLightMenu');
					}

					break;
				}
			}
		}

		/**
		 * ---
		 * Tự động khớp với request hiện tại.
		 * ---
		 * Khi truy cập submenu, highlight nó.
		 */
		else {
			if (@preg_match('/' . $this->funcs->_regexPath($this->menu_slug) . '$/iu', $currentRequest)) {
				add_filter('submenu_file', function($submenu_file) {
					return $this->menu_slug;
				});
			}
		}
	}

	private function matchCurrentAccess() {
		$currentRequest = $this->request->getRequestUri();

		/**
		 * ---
		 * Tùy chọn khớp với request hiện tại.
		 * ---
		 * Xử lý "urlsMatchCurrentAccess".\
		 * Nếu có một trong các url khớp với request hiện tại, thì chạy các code bên trong.
		 */
		if (!empty($this->urlsMatchCurrentAccess) && is_array($this->urlsMatchCurrentAccess)) {
			foreach ($this->urlsMatchCurrentAccess as $urlMatchCurrentAccess) {
				// Nếu URL không phải regex, hãy chuyển nó thành regex.
				if (!str_starts_with($urlMatchCurrentAccess, '/')) {
					$urlMatchCurrentAccess = '/'.$this->funcs->_regexPath($urlMatchCurrentAccess).'/iu';
				}

				if (@preg_match($urlMatchCurrentAccess, $currentRequest)) {
					$this->assets();

					if ($this->screenId) {
						$this->overrideCurrentScreen();
					}

					if ($this->pagenow) {
						$this->overridePageNow();
					}

					$this->overridePageTitle();
					$this->maybeCallIndexMethod();

					if (method_exists($this, 'matchedCurrentAccess')) {
						$this->callAdminPageMethod('matchedCurrentAccess');
					}

					if ($this->showScreenOptions) {
						$this->showScreenOptions();
					}

					break;
				}
			}
		}

		/**
		 * ---
		 * Tự động khớp với request hiện tại.
		 * ---
		 * Khi $this->menu_slug khớp với request hiện tại => đang truy cập vào menu_slug này.\
		 * Chạy các code bên trong.
		 */
		else {
			if (@preg_match('/'.$this->funcs->_regexPath($this->menu_slug).'$/iu', $currentRequest)) {
				$this->assets();

				if ($this->screenId) {
					$this->overrideCurrentScreen();
				}

				if ($this->pagenow) {
					$this->overridePageNow();
				}

				$this->overridePageTitle();
				$this->maybeCallIndexMethod();

				if (method_exists($this, 'matchedCurrentAccess')) {
					$this->callAdminPageMethod('matchedCurrentAccess');
				}

				if ($this->showScreenOptions) {
					$this->showScreenOptions();
				}
			}
		}
	}

	/*
	 *
	 */

//	public function beforeInit() {}

//	public function afterAddAdminPage($adminPage) {}

//	public function beforeLoadAdminPage($adminPage) {}

//	public function beforeInLoadAdminPage($adminPage) {}

//	public function afterInLoadAdminPage($adminPage) {}

//	public function afterLoadAdminPage($adminPage) {}

//	public function afterInit() {}

	/*
	 *
	 */

//	public function beforeForceInit() {}

//	public function afterAddAdminPageForce($adminPage) {}

//	public function beforeLoadAdminPageForce($adminPage) {}

//	public function beforeInLoadAdminPageForce($adminPage) {}

//	public function afterInLoadAdminPageForce($adminPage) {}

//	public function afterLoadAdminPageForce($adminPage) {}

//	public function afterForceInit() {}

	/*
	 *
	 */

//	public function matchedHighLightMenu() {}

//	public function matchedCurrentAccess() {}

	/*
	 *
	 */

	public function assets() {
//		if ($this->calledAssets) return;

		add_action('admin_enqueue_scripts', function() {
			/**
			 * Enqueue "dashboard" scripts để có thể sắp xếp gửi Ajax khi sắp xếp metaboxes và screen options trong admin page.
			 */
			wp_enqueue_script('dashboard');

			if (method_exists($this, 'styles')) {
				$this->callAdminPageMethod('styles');
			}

			if (method_exists($this, 'scripts')) {
				$this->callAdminPageMethod('scripts');
			}

			if (method_exists($this, 'localizeScripts')) {
				$this->callAdminPageMethod('localizeScripts');
			}
		}, 9999999999);

//		$this->calledAssets = true;
	}

//	public function styles() {}

//	public function scripts() {}

//	public function localizeScripts() {}

}