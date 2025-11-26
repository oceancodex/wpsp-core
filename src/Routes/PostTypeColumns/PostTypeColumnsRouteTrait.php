<?php

namespace WPSPCORE\Routes\PostTypeColumns;

use WPSPCORE\Traits\HookRunnerTrait;
use WPSPCORE\Traits\RouteTrait;

trait PostTypeColumnsRouteTrait {

	use HookRunnerTrait, RouteTrait;

	public function init() {
		$this->post_type_columns();
		$this->hooks();
	}

	/*
     *
     */

	public function post_type_columns() {}

	/*
	 *
	 */

	public function column($column, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		if ($this->isPassedMiddleware($middlewares, $this->request, [
			'column' => $column,
			'all_middlewares' => $middlewares,
			'custom_properties' => $customProperties
		])) {
			if (is_array($callback)) {
				$constructParams = [
					[
						'column'            => $column,
						'callback_function' => $callback[1] ?? null,
						'custom_properties' => $customProperties,
					],
				];
				$constructParams = array_merge([
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
				], $constructParams);
				$callback        = $this->prepareRouteCallback($callback, $useInitClass, $constructParams);
				$callback[1]     = 'init';
				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($column) : $callback;
			}
			elseif (is_callable($callback)) {
				$callback();
			}
		}
	}

}