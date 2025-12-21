<?php

namespace WPSPCORE\App\Routes\MetaBoxes;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this meta_box(string $id, callable|array $callback, array $args = [])
 */
class MetaBoxes extends BaseRoute {

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
		$priority    = $route->args['priority'] ?? 10;
		$argsNumber  = $route->args['args_number'] ?? 1;

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
			$callback        = $this->prepareRouteCallback($callback, $constructParams);
			$callback[1]     = 'init';
			add_action('add_meta_boxes', $callback, $priority, $argsNumber);
		}
	}

}