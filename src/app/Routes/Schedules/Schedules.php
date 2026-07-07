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
		$requestPath = ltrim($this->request->getRequestUri(), '/\\');

		$path          = $route->path;
		$pathRegex     = $route->pathRegex;
		$fullPath      = $route->fullPath;
		$fullPathRegex = $route->fullPathRegex;
		$callback      = $route->callback;
		$interval      = $route->args['interval'] ?? 'hourly';

		$constructParams = [
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv(),
			[
				'path'              => $path,
				'path_regex'        => $pathRegex,
				'full_path'         => $fullPath,
				'full_path_regex'   => $fullPathRegex,
				'interval'          => $interval,
				'callback_function' => $callback[1] ?? null,
			],
		];

		/**
		 * Khi callback có method là "index", thì sẽ thay đổi method thành "init".\
		 * Mục đích sẽ gọi method "init" trong Base để khởi tạo Schedule.
		 */
		$callback[1] = 'init';

		/**
		 * Vì thế, DI tại đây được triển khai với method "init".\
		 * Thành ra method "index" khi gọi trong "init" sẽ không có DI.\
		 * Cần phải truyền thêm "route" vào "extraParams" trong "constructParams"\
		 * để DI hoạt động được với method "index".
		 */
		$constructParams[3]['route'] = $route;

		/**
		 * Hợp nhất contructParams[3] (gọi là extraParams) với args được truyền từ route vào nhau.\
		 * Mục đích để callback Class có thể sử dụng được.
		 */
		$constructParams[3] = array_merge($constructParams[3], $route->args);

		/**
		 * Thực hiện các công việc với Callback.
		 * 1. Chuẩn bị callback.
		 * 2. Chuẩn bị parameters mà callback sử dụng.
		 * 3. Xử lý callback với parameters (DI).
		 * 4. Gọi callback.
		 */
		$callback   = $this->prepareRouteCallback($callback, $constructParams);
		$callParams = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1], ['route' => $route]);
		$this->resolveAndCall($callback, $callParams);
	}

	/*
	 *
	 */

	public static function interval($name, $interval, $display) {
		add_filter('cron_schedules', function($schedules) use ($name, $interval, $display) {
			$schedules[$name] = [
				'interval' => $interval,
				'display'  => $display
			];
			return $schedules;
		});
	}

}