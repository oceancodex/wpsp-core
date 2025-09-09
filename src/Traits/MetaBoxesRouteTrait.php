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

	public function meta_box($id, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $priority = 10, $argsNumber = 1): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$classArgs = array_merge([$id], $classArgs ?? []);
			$classArgs = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $classArgs);
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			add_action('add_meta_boxes', $callback, $priority, $argsNumber);
		}
	}

}