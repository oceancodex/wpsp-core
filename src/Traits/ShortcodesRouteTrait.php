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
			$customProperties = array_merge([$shortcode, $callback[1]], ['custom_properties' => $customProperties ?? []]);
			$customProperties = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $customProperties);
			$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
			$callback[1] = 'init';
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($shortcode) : $callback;
		}
	}

}