<?php

namespace WPSPCORE\App\Routes\TaxonomyColumns;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this column(string $column, callable|array $callback, array $args = [])
 */
class TaxonomyColumns extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$requestPath = trim($this->request->getRequestUri(), '/\\');

		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;

		if ($this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])) {
			$constructParams = [
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
				[
					'path'              => $path,
					'full_path'         => $fullPath,
					'callback_function' => $callback[1] ?? null,
				],
			];

			/**
			 * Khi callback có method là "index", thì sẽ thay đổi method thành "init".\
			 * Mục đích sẽ gọi method "init" trong Base để khởi tạo Taxonomy column.
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