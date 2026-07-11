<?php

namespace WPSPCORE\App\Integrations\LaravelIgnition\ContextProviders;

use Illuminate\Http\Request as LaravelRequest;
use Spatie\LaravelIgnition\ContextProviders\LaravelRequestContextProvider;

class WPSPRequestContextProvider extends LaravelRequestContextProvider {

	/** @var \WPSPCORE\App\Routes\RouteManager */
	public $routeManager;
	public $currentRoute;

	/**
	 * @param LaravelRequest                    $request
	 * @param \WPSPCORE\App\Routes\RouteManager $routeManager
	 */
	public function __construct(LaravelRequest $request, $routeManager) {
		parent::__construct($request);
		$this->routeManager = $routeManager;
		$this->currentRoute = $routeManager->currentRoute();
	}

	/** @return null|array<string, mixed> */
	public function getRoute(): array|null {
		return [
			'route'            => $this->currentRoute->getName(),
			'routeParameters'  => [$this->currentRoute->parameters()],
			'controllerAction' => $this->currentRoute->getActionName(),
			'middleware'       => $this->currentRoute->gatherMiddleware(),
		];
	}

}