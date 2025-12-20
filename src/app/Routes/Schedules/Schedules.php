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
		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;
		$interval    = $route->args['interval'] ?? 'hourly';
		$requestPath = trim($this->request->getRequestUri(), '/\\');

		$constructParams = [
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv(),
			[
				'path'              => $path,
				'full_path'         => $fullPath,
				'interval'          => $interval,
				'callback_function' => $callback[1] ?? null,
			],
		];

		// Init schedule.
		$callback[1] = 'init';
		$callback    = $this->prepareRouteCallback($callback, $constructParams);
		$callParams  = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1]);
		$this->resolveAndCall($callback, $callParams);
	}

}