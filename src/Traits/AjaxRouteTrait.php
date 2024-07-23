<?php

namespace WPSPCORE\Traits;

trait AjaxRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
		$this->apis();
		$this->hooks();
	}

	/*
	 *
	 */

	public function apis() {}

	/*
	 *
	 */

	public function get($action, $callback, $nopriv = false, $useInitClass = false, $classArgs = [], $middleware = null): void {
		if (wp_doing_ajax() && $this->request->isMethod('GET') && $this->isPassedMiddleware($middleware, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			add_action('wp_ajax_' . $action, $callback);
			if ($nopriv) {
				add_action('wp_ajax_nopriv_' . $action, $callback);
			}
		}
	}

	public function post($action, $callback, $nopriv = false, $useInitClass = false, $classArgs = [], $middleware = null): void {
		if (wp_doing_ajax() && $this->request->isMethod('POST') && $this->isPassedMiddleware($middleware, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			add_action('wp_ajax_' . $action, $callback);
			if ($nopriv) {
				add_action('wp_ajax_nopriv_' . $action, $callback);
			}
		}
	}

}