<?php

namespace WPSPCORE\App\Routes\Actions;

use WPSPCORE\App\Routes\BaseRoute;

/**
 * @method static $this action(string $action, callable|array $callback, array $args = [])
 */
class Actions extends BaseRoute {

	public function beforeConstruct() {}

	/**
	 * Xử lý route đã được đăng ký thông qua Route Manager.\
	 * RouteManager::executeAllRoutes()
	 */
	public function execute($route) {
		$requestPath = ltrim($this->request->getRequestUri(), '/\\');

		$path         = $route->path;
		$fullPath     = $route->fullPath;
		$callback     = $route->callback;
		$middlewares  = $route->middlewares;
		$priority     = $route->args['priority'] ?? 10;
		$acceptedArgs = $route->args['accepted_args'] ?? 1;

		if ($this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])) {
			if (is_array($callback) || is_callable($callback) || is_null($callback[1])) {
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
				$callback = $this->prepareRouteCallback($callback, $constructParams);
//				$callParams = $this->getCallParams($path, $fullPath, $requestPath, $callback[0], $callback[1], ['route' => $route]);
//				$callback = $this->resolveCallback($callback, $callParams);
//				add_action($fullPath, $callback, $priority, $acceptedArgs);

				/**
				 * Xử lý như thế này để có thể DI callback.\
				 * Ví dụ: add_action('save_post'); sẽ có 3 đối số: $post_id, $post, $update
				 *
				 * Tuy nhiên callback:\
				 * updatePost($post_id, $post, $update, Request $request, TestService $testService)
				 *
				 * Nếu DI như thông thường thì sẽ 3 đối số của WordPress sẽ null.\
				 * Vì vậy, cần phải truyền $wpParams vào thêm để xử lý callback params (DI).
				 *
				 * Lúc này có thể viết Callback như sau:
				 *
				 * - updatePost($post_id, $post, $update, Request $request, TestService $testService)
				 * - updatePost(TestService $testService, $post_id, $post, $update, Request $request)
				 */
				add_action(
					$fullPath,
					function(...$wpParams) use ($path, $fullPath, $requestPath, $callback, $route) {
						$callParams = $this->getCallParams(
							$path,
							$fullPath,
							$requestPath,
							$callback[0],
							$callback[1],
							['route' => $route],
							$wpParams
						);

						return $this->resolveAndCall(
							$callback,
							$callParams
						);
					},
					$priority,
					$acceptedArgs
				);
			}
		}
	}

}