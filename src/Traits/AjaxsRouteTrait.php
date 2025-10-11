<?php

namespace WPSPCORE\Traits;

trait AjaxsRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->ajaxs();
		$this->hooks();
	}

	/*
	 *
	 */

	public function ajaxs() {}

	/*
	 *
	 */

	public function get($action, $callback, $nopriv = false, $useInitClass = false, $customProperties = [], $middleware = null) {
		if (wp_doing_ajax() && $this->request->isMethod('GET') && $this->isPassedMiddleware($middleware, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
			add_action('wp_ajax_' . $action, $callback);
			if ($nopriv) {
				add_action('wp_ajax_nopriv_' . $action, $callback);
			}
		}
	}

	public function post($action, $callback, $nopriv = false, $useInitClass = false, $customProperties = [], $middleware = null) {
		if (wp_doing_ajax() && $this->request->isMethod('POST') && $this->isPassedMiddleware($middleware, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
			add_action('wp_ajax_' . $action, $callback);
			if ($nopriv) {
				add_action('wp_ajax_nopriv_' . $action, $callback);
			}
		}
	}

}