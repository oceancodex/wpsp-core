<?php

namespace WPSPCORE\App\Routes\WPRoles;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method $this wp_role(string $role, callable|array $callback, array $args = [])
 */
class WPRoles extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$request     = $this->request;
		$requestPath = trim($request->getRequestUri(), '/\\');

		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;

		$passedMiddlewares = $this->isPassedMiddleware($middlewares, $request, ['route' => $route]);
		if ($passedMiddlewares) {
			$constructParams = [
				$this->mainPath,
				$this->rootNamespace,
				$this->prefixEnv,
				[
					'path'              => $path,
					'full_path'         => $fullPath,
					'callback_function' => $callback[1] ?? null,
				],
			];

			/**
			 * Khi callback có method là "index", thì sẽ thay đổi method thành "init".\
			 * Mục đích sẽ gọi method "init" trong Base để khởi tạo Role.
			 */
			$callback[1] = 'init';

			/**
			 * Vì thế, DI tại đây được triển khai với method "init".\
			 * Thành ra method "index" khi gọi trong "init" sẽ không có DI.\
			 * Cần phải truyền thêm "route" vào "extraParams" trong "constructParams"\
			 * để DI hoạt động được với method "index".
			 */
			$constructParams[3]['route'] = $route;

			$callback   = $this->prepareRouteCallback($callback, $constructParams);
			$callParams = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1], ['route' => $route]);
			$this->resolveAndCall($callback, $callParams);
		}
	}

}