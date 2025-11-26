<?php

namespace WPSPCORE\Routes\MetaBoxes;

use WPSPCORE\Traits\HookRunnerTrait;
use WPSPCORE\Traits\RouteTrait;

trait MetaBoxesRouteTrait {

	use HookRunnerTrait, RouteTrait;

	public function init() {
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

	public function meta_box($id, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10, $argsNumber = 1) {
		if ($this->isPassedMiddleware($middlewares, $this->request, [
			'id' => $id,
			'all_middlewares' => $middlewares,
			'custom_properties' => $customProperties
		])) {
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
				$this->funcs->_getPrefixEnv()
			], $constructParams);
			$callback         = $this->prepareRouteCallback($callback, $useInitClass, $constructParams);
			$callback[1]      = 'init';
			add_action('add_meta_boxes', $callback, $priority, $argsNumber);
		}
	}

}