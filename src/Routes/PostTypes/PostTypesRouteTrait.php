<?php

namespace WPSPCORE\Routes\PostTypes;

use WPSPCORE\Traits\HookRunnerTrait;
use WPSPCORE\Traits\RouteTrait;

trait PostTypesRouteTrait {

	use HookRunnerTrait, RouteTrait;

	public function init() {
		$this->post_types();
		$this->hooks();
	}

	/*
     *
     */

	public function post_types() {}

	/*
	 *
	 */

	public function post_type($postType, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		$requestPath = trim($this->request->getRequestUri(), '/\\');
		if ($this->isPassedMiddleware($middlewares, $this->request, [
			'post_type' => $postType,
			'middlewares' => $middlewares,
			'custom_properties' => $customProperties
		])) {
			if (is_array($callback) || is_callable($callback) || is_null($callback[1])) {
				$constructParams = [
					[
						'post_type'         => $postType,
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
				$callParams = $this->getCallParams($postType, $postType, $requestPath, $callback[0], $callback[1]);
				$this->resolveAndCall($callback, $callParams);
//				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($postType) : $callback;
			}
//			elseif (is_callable($callback)) {
//				$callback();
//			}
		}
	}

}