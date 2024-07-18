<?php

namespace WPSPCORE\Traits;

trait AjaxRouteTrait {

	public function init(): void {
		$this->apis();
	}

	/*
	 *
	 */

	public function apis() {}

	/*
	 *
	 */

	public function group($callback, $middlewares = null): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$callback();
		}
	}

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