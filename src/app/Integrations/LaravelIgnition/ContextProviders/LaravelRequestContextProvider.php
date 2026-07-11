<?php

namespace WPSPCORE\App\Integrations\LaravelIgnition\ContextProviders;

class LaravelRequestContextProvider extends \Spatie\LaravelIgnition\ContextProviders\LaravelRequestContextProvider {

	/** @return null|array<string, mixed> */
	public function getRoute(): array|null {

		return [
			'route'            => '1',
			'routeParameters'  => [],
			'controllerAction' => 'a',
			'middleware'       => ['a'],
		];
	}


}
