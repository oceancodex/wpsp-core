<?php

namespace WPSPCORE\Traits;

trait UserMetaBoxesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->user_meta_boxes();
		$this->hooks();
	}

	/*
     *
     */

	public function user_meta_boxes() {}

	/*
	 *
	 */

	public function user_meta_box($id, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10, $argsNumber = 1) {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$constructParams = [
				[
					'id'                => $id,
					'callback_function' => $callback[1] ?? null,
					'custom_properties' => $customProperties,
				],
			];
			$constructParams = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
			], $constructParams);
			$callback = $this->prepareCallback($callback, $useInitClass, $constructParams);
			add_action('show_user_profile', $callback, $priority, $argsNumber);
			add_action('edit_user_profile', $callback, $priority, $argsNumber);
		}
	}

}