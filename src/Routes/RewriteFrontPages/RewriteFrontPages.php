<?php

namespace WPSPCORE\Routes\RewriteFrontPages;

use WPSPCORE\Routes\BaseRoute;

/**
 * @method static $this get(string $path, callable|array $callback)
 * @method static $this post(string $path, callable|array $callback)
 * @method static $this put(string $path, callable|array $callback)
 * @method static $this patch(string $path, callable|array $callback)
 * @method static $this delete(string $path, callable|array $callback)
 * @method static $this options(string $path, callable|array $callback)
 * @method static $this head(string $path, callable|array $callback)
 */
class RewriteFrontPages extends BaseRoute {

	public function beforeConstruct(): void {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route): void {
		$this->executeMethod($route);
	}

}