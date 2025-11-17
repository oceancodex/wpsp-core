<?php

namespace WPSPCORE\Traits;

trait ShortcodesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
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

	public function shortcode($shortcode, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		if ($this->isPassedMiddleware($middlewares, $this->request, ['short_code' => $shortcode, 'custom_properties' => $customProperties])) {
			$constructParams = [
				[
					'shortcode'         => $shortcode,
					'callback_function' => $callback[1] ?? null,
					'custom_properties' => $customProperties,
				],
			];
			$constructParams = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $constructParams);
			$callback = $this->prepareCallback($callback, $useInitClass, $constructParams);
			$callback[1] = 'init';
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($shortcode) : $callback;
		}
	}

}