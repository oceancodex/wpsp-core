<?php

namespace WPSPCORE\App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

	/**
	 * Register any application services.
	 */
	public function register() {
		//
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot() {
		$this->app['view']->prependNamespace(
			'laravel-exceptions-renderer',
			__DIR__.'/../../resources/views/exceptions/renderer'
		);
	}

}