<?php

namespace WPSPCORE\App\Routes\RewriteFrontPages;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this get(string $path, callable|array $callback, array $args = [])
 * @method static $this post(string $path, callable|array $callback, array $args = [])
 * @method static $this put(string $path, callable|array $callback, array $args = [])
 * @method static $this patch(string $path, callable|array $callback, array $args = [])
 * @method static $this delete(string $path, callable|array $callback, array $args = [])
 * @method static $this options(string $path, callable|array $callback, array $args = [])
 * @method static $this head(string $path, callable|array $callback, array $args = [])
 */
class RewriteFrontPages extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$requestPath = trim($this->request->getRequestUri(), '/\\');

		$path        = $route->path;
		$fullPath    = $route->fullPath;
		$method      = $route->method;
		$callback    = $route->callback;
		$middlewares = $route->middlewares;

		if (
			$this->request->method() == strtoupper($method)
			&& preg_match('/' . $this->funcs->_regexPath($fullPath) . '/iu', $requestPath)
			&& $this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])
		) {
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
			 * Mục đích sẽ gọi method "init" trong Base để khởi tạo Rewrite front page.
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