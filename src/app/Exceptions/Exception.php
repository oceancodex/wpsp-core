<?php

namespace WPSPCORE\App\Exceptions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Exceptions\Renderer\Listener;
use Illuminate\Http\Request;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use WPSPCORE\App\Routes\RouteManager;

class Exception extends \Illuminate\Foundation\Exceptions\Renderer\Exception {

	protected ?RouteManager $routeManager = null;

	public function __construct(FlattenException $exception, Request $request, Listener $listener, string $basePath, $routeManager = null) {
		parent::__construct($exception, $request, $listener, $basePath);
		$this->routeManager = $routeManager;
	}

	/**
	 * Get the application's route context.
	 *
	 * @return array<string, string>
	 */
	public function applicationRouteContext() {
		$matchedRoutes = $this->routeManager?->matchedRoutes();

		$routers = [];

		foreach ($matchedRoutes as $route) {
			$routers[] = $route ? array_filter([
				'controller' => $route->getActionName(),
				'route name' => $route->getName() ?: null,
				'middleware' => implode(', ', array_map(function($middleware) {
					return $middleware instanceof Closure ? 'Closure' : $middleware;
				}, $route->gatherMiddleware())),
				'parameters' => $route->parameters ? json_encode(array_map(
					fn ($value) => $value instanceof Model ? $value->withoutRelations() : $value,
					$route->parameters
				), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null
			]) : [];
		}

		return $routers;
	}

}
