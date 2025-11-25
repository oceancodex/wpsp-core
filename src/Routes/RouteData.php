<?php

namespace WPSPCORE\Routes;

class RouteData {

	public $method;
	public $path;
	public $callback;

	public $name        = null;
	public $middlewares = [];

	public array $nameStack  = [];
//	public bool  $routeNamed = false;

	public function __construct(string $method, string $path, $callback, array $groupAttributes) {
		// prefix
		$prefix = $groupAttributes['prefix'] ?? '';
		if ($prefix !== '') {
			$prefix = rtrim($prefix, '/') . '/';
		}

		$this->method   = strtoupper($method);
		$this->path     = $prefix . ltrim($path, '/');
		$this->callback = $callback;

		// unique middleware
		$this->middlewares = isset($groupAttributes['middlewares'])
			? array_values(array_unique($groupAttributes['middlewares']))
			: [];
	}

	public function setGroupNameStack(array $stack) {
		$this->nameStack = $stack;
	}

	/**
	 * Khi route gọi ->name('foo')
	 * Ta mới gắn prefix name từ group
	 */
	public function name(string $name) {
//		$this->routeNamed = true;

		// prefix từ stack
		$prefix = implode('', $this->nameStack ?? []);

		$this->name = $prefix . $name;

		return $this;
	}

	public function middleware($middlewares) {
		$middlewares       = is_array($middlewares) ? $middlewares : [$middlewares];
		$this->middlewares = array_values(array_unique(array_merge($this->middlewares, $middlewares)));
		return $this;
	}

}
