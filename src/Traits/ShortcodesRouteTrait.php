<?php

namespace WPSPCORE\Traits;

trait ShortcodesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
		$this->shortcodes();
		$this->hooks();
	}

	/*
     *
     */
	public function shortcodes() {}

	/*
	 *
	 */

	public function shortcode($shortcode, $callback, $useInitClass = false, $customProperties = [], $middlewares = null): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
			add_shortcode($shortcode, $callback);
		}
	}

}