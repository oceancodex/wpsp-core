<?php

namespace WPSPCORE\App\Routes\Schedules;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this schedule(string $hook, callable|array $callback, array $args = [])
 */
class Schedules extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$hook     = $route->fullPath;
		$callback = $route->callback;
		$interval = $route->args['interval'] ?? 'daily';

		$constructParams = [
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv(),
			[
				'hook'              => $hook,
				'callback_function' => $callback[1] ?? null,
			],
		];

		$callback = $this->prepareRouteCallback($callback, $constructParams);
		add_action($hook, $callback);
		if (!wp_next_scheduled($hook)) {
			wp_schedule_event(time(), $interval, $hook);
		}
		register_deactivation_hook($this->funcs->_getMainFilePath(), function() use ($hook) {
			wp_unschedule_hook($hook);
//			$timestamp = wp_next_scheduled($hook);
//			if ($timestamp) wp_unschedule_event($timestamp, $hook);
		});
	}

}