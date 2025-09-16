<?php

namespace WPSPCORE\Traits;

trait MetaBoxesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
		$this->meta_boxes();
		$this->hooks();
	}

	/*
     *
     */
	public function meta_boxes() {}

	/*
	 *
	 */

	public function meta_box($id, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10, $argsNumber = 1): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$customProperties = array_merge([$id, $callback[1]], $customProperties ?? []);
			$customProperties = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $customProperties);
			$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
			$callback[1] = 'init';
			add_action('add_meta_boxes', $callback, $priority, $argsNumber);
		}
	}

}