<?php

namespace WPSPCORE\App\Integrations\LaravelIgnition;

use Illuminate\Http\Request;
use Spatie\FlareClient\Report;
use Spatie\Ignition\ErrorPage\Renderer;
use Spatie\LaravelIgnition\ContextProviders\LaravelRequestContextProvider;
use WPSPCORE\App\Integrations\LaravelIgnition\ContextProviders\WPSPRequestContextProvider;
use WPSPCORE\App\Integrations\LaravelIgnition\ErrorPage\ErrorPageViewModel;

class Ignition extends \Spatie\Ignition\Ignition {

	public $app;
	public $routeManager;

	/*
	 *
	 */

	public function __construct($flare = null, $app = null, $routeManager = null) {
		parent::__construct($flare);
		$this->app = $app;
		$this->routeManager = $routeManager;
	}

	/*
	 *
	 */

	public static function make(): static {
		return new static();
	}

	/**
	 * This is the main entrypoint for laravel-ignition. It only renders the exception.
	 * Sending the report to Flare is handled in the laravel-ignition log handler.
	 */
	public function renderException(\Throwable $throwable, ?Report $report = null): void {
		$this->setUpFlare();

		$report ??= $this->createReport($throwable);

		$report->useContext(new WPSPRequestContextProvider($this->app->request, $this->routeManager));

		$viewModel = new ErrorPageViewModel(
			$throwable,
			$this->ignitionConfig,
			$report,
			$this->solutionProviderRepository->getSolutionsForThrowable($throwable),
			$this->solutionTransformerClass,
			$this->customHtmlHead,
			$this->customHtmlBody,
		);

		(new Renderer())->render(['viewModel' => $viewModel], self::viewPath('errorPage'));
	}

}