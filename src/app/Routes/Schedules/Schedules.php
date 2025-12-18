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
		$hook        = $route->fullPath;
		$callback    = $route->callback;
		$interval    = $route->args['interval'] ?? 'daily';
		$requestPath = trim($this->request->getRequestUri(), '/\\');

		// Đăng ký schedule nếu chưa tồn lại.
		if (!wp_next_scheduled($hook)) {
			wp_schedule_event(time(), $interval, $hook);
		}

		// Xóa schedule khi plugin bị hủy kích hoạt
		register_deactivation_hook($this->funcs->_getMainFilePath(), function() use ($hook) {
			wp_unschedule_hook($hook);
//			$timestamp = wp_next_scheduled($hook);
//			if ($timestamp) wp_unschedule_event($timestamp, $hook);
		});

		$constructParams = [
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv(),
			[
				'hook'              => $hook,
				'callback_function' => $callback[1] ?? null,
			],
		];

		// Chạy callback của Schedule khi được gọi.
		$callback[1] = 'init';
		$callback = $this->prepareRouteCallback($callback, $constructParams);
		$callParams = $this->getCallParams($hook, $hook, $requestPath, $callback[0], $callback[1]);
		$this->resolveAndCall($callback, $callParams);
	}

}