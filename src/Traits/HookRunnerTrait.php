<?php

namespace WPSPCORE\Traits;

trait HookRunnerTrait {

	public function hooks(): void {
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

	public function hook($type, $hook, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $priority = 10, $argsNumber = 0): void {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			if ($type == 'action') {
				add_action($hook, $callback, $priority, $argsNumber);
			}
			elseif ($type == 'filter') {
				add_filter($hook, $callback, $priority, $argsNumber);
			}
		}
	}

	public function action($hook, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $priority = 10, $argsNumber = 0): void {
		$this->hook('action', $hook, $callback, $useInitClass, $classArgs, $middlewares, $priority, $argsNumber);
	}

	public function filter($hook, $callback, $useInitClass = false, $classArgs = [], $middlewares = null, $priority = 10, $argsNumber = 0): void {
		$this->hook('filter', $hook, $callback, $useInitClass, $classArgs, $middlewares, $priority, $argsNumber);
	}

}