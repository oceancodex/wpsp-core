<?php

namespace WPSPCORE\App\Traits;

trait HookRunnerTrait {

	public function hooks() {
		$this->actions();
		$this->filters();
	}

	/*
	 *
	 */

	public function actions() {}

	public function filters() {}

	/*
	 *
	 */

	public function hook($route) {
		$method       = $route->method;
		$path         = $route->path;
		$fullPath     = $route->fullPath;
		$callback     = $route->callback;
		$middlewares  = $route->middlewares;
		$priority     = $route->args['priority'] ?? 10;
		$acceptedArgs = $route->args['accepted_args'] ?? 1;

		if (static::isPassedMiddleware($route->middlewares, $this->request, [
			'method'      => $method,
			'path'        => $path,
			'middlewares' => $middlewares,
		])) {
			$constructParams = [
				[
					'path'      => $path,
					'full_path' => $fullPath,
					'method'    => $method,
				],
			];
			$constructParams = array_merge([
				$this->mainPath,
				$this->rootNamespace,
				$this->prefixEnv,
			], $constructParams);

			$requestPath = trim($this->request->getRequestUri(), '/\\');
			$callback    = $this->prepareRouteCallback($callback, $constructParams);
			$callParams  = $this->getCallParams($path, $fullPath, $requestPath, $callback);
			$callback    = $this->resolveCallback($callback, $callParams);
			if ($method == 'action') {
				add_action($path, $callback, $priority, $acceptedArgs);
			}
			elseif ($method == 'filter') {
				add_filter($path, $callback, $priority, $acceptedArgs);
			}
		}
	}

	public function remove_hook($route) {
		$method       = $route->method;
		$path         = $route->path;
		$fullPath     = $route->fullPath;
		$callback     = $route->callback;
		$middlewares  = $route->middlewares;
		$priority     = $route->args['priority'] ?? 10;

		if ($this->isPassedMiddleware($middlewares, $this->request, [
			'method'      => $method,
			'path'        => $path,
			'middlewares' => $middlewares,
		])) {
			$constructParams = [
				[
					'path'      => $path,
					'full_path' => $fullPath,
					'method'    => $method,
				],
			];
			$constructParams = array_merge([
				$this->mainPath,
				$this->rootNamespace,
				$this->prefixEnv,
			], $constructParams);

			$callback = $this->prepareRouteCallback($callback, $constructParams);
			if ($method == 'remove_action') {
				remove_action($path, $callback, $priority);
			}
			elseif ($method == 'remove_filter') {
				remove_filter($path, $callback, $priority);
			}
		}
	}

}