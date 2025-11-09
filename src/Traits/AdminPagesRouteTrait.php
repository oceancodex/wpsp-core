<?php

namespace WPSPCORE\Traits;

trait AdminPagesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->admin_pages();
		$this->hooks();
		return $this;
	}

	public function initForRouterMap() {
		$this->admin_pages();
		return $this;
	}

	/*
     *
     */

	abstract public function admin_pages();

	/*
	 *
	 */

	public function get($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		// Build full path.
		$fullPath = $this->buildFullPath($path);

		// Merge middlewares từ stack và parameter
		$allMiddlewares = $this->getFlattenedMiddlewares();
		if ($middlewares !== null) {
			$allMiddlewares = array_merge($allMiddlewares, is_array($middlewares) ? $middlewares : [$middlewares]);
		}

		// Đánh dấu route để có thể name() sau này
		$this->markRouteForNaming($path);

		// Nếu đang build router map, chỉ lưu thông tin
		if ($this->isForRouterMap) {
			return $this;
		}
		if (!empty($callback) && is_admin() && !wp_doing_ajax() && !wp_doing_cron() && !$this->funcs->_wantsJson()) {
			$requestPath = trim($this->request->getRequestUri(), '/\\');
			if (
				(is_array($callback) || is_callable($callback) || is_null($callback[1]))
				&& (
					!isset($callback[1])
					|| $callback[1] == 'index'
					|| $this->request->get('page') == $fullPath
					|| preg_match('/' . $this->funcs->_escapeRegex($fullPath) . '$/iu', $requestPath)
				)
			) {
				if ($this->isPassedMiddleware($allMiddlewares, $this->request)) {
					$constructParams = [
						[
							'path'              => $fullPath,
							'callback_function' => $callback instanceof \Closure ? $callback : $callback[1] ?? null,
							'custom_properties' => $customProperties,
						],
					];
					$constructParams = array_merge([
						$this->funcs->_getMainPath(),
						$this->funcs->_getRootNamespace(),
						$this->funcs->_getPrefixEnv(),
					], $constructParams);

					if ($callback instanceof \Closure) {
						add_action('admin_menu', function() use ($fullPath, $callback) {
							if (is_array($callback)) {
								$callbackRef = new \ReflectionMethod($callback[0], $callback[1]);
							} else {
								$callbackRef = new \ReflectionFunction($callback);
							}
							$params      = $callbackRef->getParameters();
							$args        = [];
							foreach ($params as $param) {
								$name = $param->getName();

								if ($param->isDefaultValueAvailable()) {
									$default = $param->getDefaultValue();
								}
								else {
									$default = null;
								}
								$args[$name] = $default;
							}
							if (isset($args['is_submenu_page']) && $args['is_submenu_page']) {
								add_submenu_page(
									$args['parent_slug'] ?? 'options-general.php',
									$args['page_title'] ?? $fullPath,
									$args['menu_title'] ?? $fullPath,
									$args['capability'] ?? 'manage_options',
									$args['menu_slug'] ?? $fullPath,
									$callback,
									$args['position'] ?? null
								);
							}
							else {
								add_menu_page(
									$args['page_title'] ?? $fullPath,
									$args['menu_title'] ?? $fullPath,
									$args['capability'] ?? 'manage_options',
									$args['menu_slug'] ?? $fullPath,
									$callback,
									$args['icon_url'] ?? null,
								);
							}
						});
					}
					else {
						if (isset($callback[1]) && is_string($callback[1]) && $callback[1] !== 'index') {
							if (preg_match('/' . $this->funcs->_escapeRegex($fullPath) . '$/iu', $requestPath)) {
								$callback = $this->prepareCallback($callback, $useInitClass, $constructParams);
								$callParams = $this->getCallParams($path, $requestPath, $callback[0], $callback[1]);
								isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}(...$callParams) : $callback;
							}
						}
						else {
							$callback = $this->prepareCallback($callback, $useInitClass, $constructParams);
							if (($callback[1] == 'index' || !isset($callback[1]))) $callback[1] = 'init';
							$callParams = $this->getCallParams($path, $requestPath, $callback[0], $callback[1]);
							isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}(...$callParams) : $callback;
						}
					}
				}
				elseif (preg_match('/' . $this->funcs->_escapeRegex($fullPath) . '$/iu', $requestPath)) {
					wp_die(
						'<h1>ERROR: 403 - Truy cập bị từ chối</h1>' .
						'<p>Bạn không được phép truy cập vào trang này.</p>',
						'ERROR: 403 - Truy cập bị từ chối',
						[
							'response'  => 403,
							'back_link' => true,
						]
					);
				}
			}
		}

		// Reset middleware khi gọi xong function.
		$this->middlewareStack = [];

		return $this;
	}

	public function post($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		// Build full path.
		$fullPath = $this->buildFullPath($path);

		// Merge middlewares
		$allMiddlewares = $this->getFlattenedMiddlewares();
		if ($middlewares !== null) {
			$allMiddlewares = array_merge($allMiddlewares, is_array($middlewares) ? $middlewares : [$middlewares]);
		}

		// Đánh dấu route để có thể name() sau này
		$this->markRouteForNaming($path);

		// Nếu đang build router map, chỉ lưu thông tin
		if ($this->isForRouterMap) {
			return $this;
		}

		if (!empty($callback) && is_admin() && !wp_doing_ajax() && !wp_doing_cron() && !$this->funcs->_wantsJson()) {
			if ($this->request->isMethod('POST')) {
				$this->executeHiddenMethod($fullPath, $callback, $useInitClass, $customProperties, $allMiddlewares);
			}
		}

		// Reset middleware khi gọi xong function.
		$this->middlewareStack = [];

		return $this;
	}

	/*
	 *
	 */

	public function executeHiddenMethod($path, $callback, $useInitClass = false, $customProperties = [], $middlewares = null) {
		$screenOptions = $this->request->get('wp_screen_options');
		if ($screenOptions) {
			return;
		}

		$requestPath = trim($this->request->getRequestUri(), '/\\');
		if (
			(is_array($callback) || is_callable($callback))
			&&
			(isset($callback[1]) && $callback[1] !== 'index')
			&&
			(
				($this->request->get('page') == $path && preg_match('/' . $this->funcs->_escapeRegex($path) . '$/iu', $requestPath))
				|| preg_match('/' . $this->funcs->_escapeRegex($path) . '$/iu', $requestPath)
			)
		) {
			if ($this->isPassedMiddleware($middlewares, $this->request)) {
				$constructParams = [
					[
						'path'              => $path,
						'callback_function' => $callback[1] ?? null,
						'custom_properties' => $customProperties,
					],
				];
				$constructParams = array_merge([
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
				], $constructParams);
				$callback        = $this->prepareCallback($callback, $useInitClass, $constructParams);
				$callParams      = $this->getCallParams($path, $requestPath, $callback[0], $callback[1]);
				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}(...$callParams) : $callback;
			}
			else {
				wp_die(
					'<h1>ERROR: 403 - Truy cập bị từ chối</h1>' .
					'<p>Bạn không được phép truy cập vào trang này.</p>',
					'ERROR: 403 - Truy cập bị từ chối',
					[
						'response'  => 403,
						'back_link' => true,
					]
				);
			}
		}
	}

}