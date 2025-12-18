<?php

namespace WPSPCORE\App\WordPress\Schedules;

use WPSPCORE\App\Routes\RouteTrait;
use WPSPCORE\BaseInstances;

abstract class BaseSchedule extends BaseInstances {

	use RouteTrait;

	public $hook              = null;
	public $interval          = 'hourly';
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'] ?? null;
		$this->overrideHook($this->extraParams['hook'] ?? null);
	}

	private function overrideHook($hook = null) {
		if ($hook && !$this->hook) {
			$this->hook = $hook;
		}
	}

	/*
	 *
	 */

	public function init($hook = null, $interval = 'hourly') {
		$callback = null;
		$callbackFunction = $this->callback_function;
		if ($callbackFunction && method_exists(static::class, $callbackFunction)) {
			$requestPath = trim($this->request->getRequestUri(), '/\\');
			$constructParams = [
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
				[
					'path'              => $hook ?? $this->hook,
					'full_path'         => $hook ?? $this->hook,
					'callback_function' => $callbackFunction,
				],
			];
			$callback = $this->prepareRouteCallback([static::class, $callbackFunction], $constructParams);
			$callParams = $this->getCallParams($this->hook, $this->hook, $requestPath, static::class, $callbackFunction);
			$callback = $this->resolveCallback($callback, $callParams);
		}
		add_action($hook, $callback);
	}

}