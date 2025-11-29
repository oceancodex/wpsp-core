<?php

namespace WPSPCORE\Routes\UserMetaBoxes;

use WPSPCORE\Routes\BaseRoute;

/**
 * @method static $this user_meta_box(string $id, callable|array $callback, array $args = [])
 */
class UserMetaBoxes extends BaseRoute {

	public function beforeConstruct(): void {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route): void {
		$id          = $route->fullPath;
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
					'id'                => $id,
					'callback_function' => $callback[1] ?? null,
				],
			];

			$callback = $this->prepareRouteCallback($callback, $constructParams);
			add_action('show_user_profile', $callback, $priority, $argsNumber);
			add_action('edit_user_profile', $callback, $priority, $argsNumber);
		}
	}

}