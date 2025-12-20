<?php

namespace WPSPCORE\App\Routes\RewriteFrontPages;

use WPSPCORE\App\Traits\HookRunnerTrait;

trait RewriteFrontPagesRouteTrait {

	use HookRunnerTrait;

	public $funcs;
	public $mainPath;
	public $rootNamespace;
	public $prefixEnv;

	/*
	 *
	 */

	public function __construct() {
		$this->beforeInstanceConstruct();
	}

	/*
	 *
	 */

	public function register() {
		$this->addQueryVars();
		$this->rewrite_front_pages();
		$this->hooks();
	}

	/*
	 *
	 */

	private function addQueryVars() {
		add_filter('query_vars', function($query_vars) {
			$query_vars[] = 'is_rewrite';
			$query_vars[] = $this->funcs->_config('app.short_name') . '_rewrite_ident';
			for ($i = 1; $i <= 20; $i++) {
				$query_vars[] = $this->funcs->_config('app.short_name') . '_rewrite_group_' . $i;
			}
			return $query_vars;
		}, 10, 1);

		// Chặn redirect canonical cho các trang front page vì sử dụng "post_type" và "pagename" trong rewrite rules.
		add_filter('redirect_canonical', function($redirect_url, $requested_url) {
			if (get_query_var('is_rewrite') == 'true') return false;
			return $redirect_url;
		}, 10, 2);
	}

	/*
     *
     */

	abstract public function rewrite_front_pages();

}