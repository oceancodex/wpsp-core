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
	public function execute($route) {}

}