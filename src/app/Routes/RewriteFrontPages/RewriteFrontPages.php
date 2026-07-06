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
		$requestPath = ltrim($this->request->getRequestUri(), '/\\');

		$permastruct   = $route->args['permastruct'] ?? false;
		$path          = $route->path;
		$pathRegex     = $route->pathRegex;
		$fullPath      = $route->fullPath;
		$fullPathRegex = $route->fullPathRegex;
		$method        = $route->method;
		$callback      = $route->callback;
		$middlewares   = $route->middlewares;

		if ($permastruct) {
			$permastructPaths = $this->preparePermastuctPaths($path, $pathRegex, $fullPath, $fullPathRegex);
			$path                = $permastructPaths['path'];
			$pathRegex           = $permastructPaths['pathRegex'];
			$fullPath            = $permastructPaths['fullPath'];
			$fullPathRegex       = $permastructPaths['fullPathRegex'];
		}

		try {
			if (
				$this->request->method() == strtoupper($method)
				&& (
					@preg_match('/' . $this->funcs->_regexPath($fullPath) . '/iu', $requestPath)
					|| @preg_match('/' . $this->funcs->_regexPath($fullPath) . '$/iu', $requestPath)
					|| @preg_match('/' . $fullPath . '/iu', $requestPath)
					|| @preg_match($fullPathRegex, $requestPath)
				)
				&& $this->isPassedMiddleware($middlewares, $this->request, ['route' => $route])
			) {
				$constructParams = [
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
					[
						'path'              => $path,
						'path_regex'        => $pathRegex,
						'full_path'         => $fullPath,
						'full_path_regex'   => $fullPathRegex,
						'callback_function' => $callback[1] ?? null,
						'permastruct'		=> $permastruct,
					],
				];

				/**
				 * Khi callback có method là "index", thì sẽ thay đổi method thành "init".\
				 * Mục đích sẽ gọi method "init" trong Base để khởi tạo Rewrite front page.
				 */
				$callback[1] = $permastruct ? 'initPermastruct' : 'init';

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
		}
		catch (\Exception $e) {
		}
	}

	/*
	 *
	 */

	public static function rewrite_tag($tag, $regex) {
		add_rewrite_tag($tag, $regex);
	}

	/*
	 *
	 */

	/**
	 * Lấy danh sách các rewrite tag trong WordPress cùng với regex tương ứng.
	 *
	 * @return array Mảng có key là rewrite tag (ví dụ: '%postname%') và value là regex (ví dụ: '([^/]+)')
	 */
	public function getRewriteTags() {
		global $wp_rewrite;

		// Kiểm tra xem đối tượng $wp_rewrite và các mảng cần thiết có tồn tại và hợp lệ không
		if (!isset($wp_rewrite) || empty($wp_rewrite->rewritecode) || empty($wp_rewrite->rewritereplace)) {
			return [];
		}

		// Kiểm tra số lượng phần tử của 2 mảng để tránh lỗi lệch index khi dùng array_combine
		if (count($wp_rewrite->rewritecode) !== count($wp_rewrite->rewritereplace)) {
			return [];
		}

		// Trả về mảng kết hợp: key là tag, value là regex
		return array_combine($wp_rewrite->rewritecode, $wp_rewrite->rewritereplace);
	}

	public function preparePermastuctPaths($path, $pathRegex, $fullPath, $fullPathRegex) {
		$rewriteTags   = $this->getRewriteTags();

		$path          = str_replace(array_keys($rewriteTags), array_values($rewriteTags), $path);
		$pathRegex     = str_replace(array_keys($rewriteTags), array_values($rewriteTags), $pathRegex);
		$fullPath      = str_replace(array_keys($rewriteTags), array_values($rewriteTags), $fullPath);
		$fullPathRegex = str_replace(array_keys($rewriteTags), array_values($rewriteTags), $fullPathRegex);

		return [
			'path'          => $path,
			'pathRegex'     => $pathRegex,
			'fullPath'      => $fullPath,
			'fullPathRegex' => $fullPathRegex,
		];
	}

}