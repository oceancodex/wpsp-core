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

	public function shortcode($shortcode, $callback, $useInitClass = false, $classArgs = [], $middlewares = null): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			add_shortcode($shortcode, $callback);
		}
	}

}