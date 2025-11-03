<?php

namespace WPSPCORE\Traits;

trait AjaxsRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init() {
		$this->ajaxs();
		$this->hooks();
	}

	public function initForRouterMap() {
		$this->ajaxs();
		return $this;
	}

	/*
	 *
	 */

	abstract public function ajaxs();

	/*
	 *
	 */

	public function get($action, $callback, $useInitClass = false, $nopriv = false, $customProperties = null, $middlewares = null) {
		// Xây dựng full path
		$fullPath = $this->buildFullPath($action);

		// Merge middlewares
		$allMiddlewares = $this->getFlattenedMiddlewares();
		if ($middlewares !== null) {
			$allMiddlewares = array_merge($allMiddlewares, is_array($middlewares) ? $middlewares : [$middlewares]);
		}

		// Đánh dấu route để có thể name() sau này
		$this->markRouteForNaming($action);

		// Nếu đang build router map, chỉ lưu thông tin
		if ($this->isForRouterMap) {
			return $this;
		}

		$hookAction = 'wp_ajax_' . ($nopriv ? 'nopriv_' : '') . $fullPath;

		add_action($hookAction, function() use ($fullPath, $callback, $useInitClass, $customProperties, $allMiddlewares) {
			if (!$this->isPassedMiddleware($allMiddlewares, $this->request)) {
				wp_send_json($this->funcs->_response(false, [], 'Access denied.', 403), 403);
				return;
			}

			$constructParams = [
				[
					'path'              => $fullPath,
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

			if (isset($callback[0]) && isset($callback[1])) {
				$callback[0]->{$callback[1]}($fullPath);
			}
			else {
				$callback($fullPath);
			}
		});

		return $this;
	}

	public function post($action, $callback, $useInitClass = false, $nopriv = false, $customProperties = null, $middlewares = null) {
		return $this->get($action, $callback, $useInitClass, $nopriv, $customProperties, $middlewares);
	}

}