<?php

namespace WPSPCORE\App\Routes\AdminPageMetaboxes;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this meta_box(string $id, callable|array $callback, array $args = [])
 */
class AdminPageMetaboxes extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$request  = $this->request;
		$method   = $route->method;
		$callback = $route->callback;

		if (!empty($callback) && is_admin() && !wp_doing_ajax() && !wp_doing_cron() && !$this->funcs->_wantsJson()) {

		}
	}

}