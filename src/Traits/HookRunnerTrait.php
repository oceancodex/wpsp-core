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

	public function hook($type, $hook, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10, $argsNumber = 1) {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
			if ($type == 'action') {
				add_action($hook, $callback, $priority, $argsNumber);
			}
			elseif ($type == 'filter') {
				add_filter($hook, $callback, $priority, $argsNumber);
			}
		}
	}

	public function action($hook, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10, $argsNumber = 1) {
		$this->hook('action', $hook, $callback, $useInitClass, $customProperties, $middlewares, $priority, $argsNumber);
	}

	public function filter($hook, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10, $argsNumber = 1) {
		$this->hook('filter', $hook, $callback, $useInitClass, $customProperties, $middlewares, $priority, $argsNumber);
	}

	/*
	 *
	 */

	public function remove_hook($type, $hook, $callback, $useInitClass = false, $customProperties = [], $middlewares = null, $priority = 10) {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
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