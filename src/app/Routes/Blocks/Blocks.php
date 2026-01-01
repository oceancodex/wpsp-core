<?php
namespace WPSPCORE\App\Routes\Blocks;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this block(string $block, callable|array $callback, array $args = [])
 */
class Blocks extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$requestPath = trim($this->request->getRequestUri(), '/\\');

		$path     = $route->path;
		$fullPath = $route->fullPath;
		$callback = $route->callback;

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

		$callback    = $this->prepareRouteCallback($callback, $constructParams);
		$callback[1] = 'init';
		$callParams  = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1], ['route' => $route]);
		$this->resolveAndCall($callback, $callParams);
	}

}