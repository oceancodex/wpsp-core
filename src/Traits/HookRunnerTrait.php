<?php

namespace WPSPCORE\Traits;

trait HookRunnerTrait {

	public function hooks() {
		$this->actions();
		$this->filters();
	}

	/*
	 *
	 */

	public function actions() {}

	public function filters() {}

	/*
	 *
	 */

	public static function hook($type, $hook, $callback, $middlewares = [], $priority = 10, $argsNumber = 1) {
		if (static::isPassedMiddleware($middlewares, static::$request, [
			'type' => $type,
			'hook' => $hook,
			'middlewares' => $middlewares,
		])) {
			$requestPath = trim(static::$request->getRequestUri(), '/\\');
			$callback = static::prepareRouteCallback($callback, []);
			$callParams = static::getCallParams($hook, $hook, $requestPath, $callback[0], $callback[1]);
			$callback = static::resolveCallback($callback, $callParams);
			if ($type == 'action') {
				add_action($hook, $callback, $priority, $argsNumber);
			}
			elseif ($type == 'filter') {
				add_filter($hook, $callback, $priority, $argsNumber);
			}
		}
	}

	public static function action($hook, $callback, $priority = 10, $argsNumber = 1) {
		return static::buildRoute(__FUNCTION__, [$hook, $callback, $priority, $argsNumber]);
	}

	public static function filter($hook, $callback, $priority = 10, $argsNumber = 1) {
		return static::buildRoute(__FUNCTION__, [$hook, $callback, $priority, $argsNumber]);
	}

	/*
	 *
	 */

	public function remove_hook($type, $hook, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10) {
		if ($this->isPassedMiddleware($middlewares, $this->request, [
			'type' => $type,
			'hook' => $hook,
			'all_middlewares' => $middlewares,
			'custom_properties' => $customProperties
		])) {
			$callback = $this->prepareRouteCallback($callback, $useInitClass, $customProperties);
			if ($type == 'action') {
				remove_action($hook, $callback, $priority);
			}
			elseif ($type == 'filter') {
				remove_filter($hook, $callback, $priority);
			}
		}
	}

	public function remove_action($hook, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10) {
		$this->remove_hook('action', $hook, $callback, $useInitClass, $customProperties, $middlewares, $priority);
	}

	public function remove_filter($hook, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10) {
		$this->remove_hook('filter', $hook, $callback, $useInitClass, $customProperties, $middlewares, $priority);
	}

}