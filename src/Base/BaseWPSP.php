<?php

namespace WPSPCORE\Base;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Http\Request;
use WPSPCORE\Console\Commands\MakeAdminPageCommand;
use WPSPCORE\Http\Middleware\StartSessionIfAuthenticated;

abstract class BaseWPSP extends BaseInstances {

	public ?Application $application = null;
	public ?\Symfony\Component\HttpFoundation\Response $response = null;

	/*
	 *
	 */

	public function setApplication(string $basePath) {
		$this->application = Application::configure($basePath)
			->withMiddleware(function(Middleware $middleware): void {})
			->withExceptions(function(Exceptions $exceptions): void {})
			->withCommands([
				MakeAdminPageCommand::class,
			])
			->create();

		$this->bootstrap();
		$this->bindings();
		$this->application->boot();
		$this->handleRequest();
	}

	public function getApplication($abstract = null, $parameters = []) {
		if ($abstract) {
			return $this->application->make($abstract, $parameters);
		}
		return $this->application;
	}

	/*
	 *
	 */

	protected function bootstrap() {
		// Load environment variables.
		(new LoadEnvironmentVariables)->bootstrap($this->application);
		$this->normalizeEnvPrefix();

		// Load config & facades.
		(new LoadConfiguration)->bootstrap($this->application);
		(new RegisterFacades)->bootstrap($this->application);
		(new RegisterProviders)->bootstrap($this->application);
	}

	protected function bindings(): void {
		$this->application->instance('files', new Filesystem());
		$this->application->instance('request', Request::capture());
	}

	protected function handleRequest(): void {
		$request = $this->application['request'];
		$kernel = $this->application->make(Kernel::class);
		$response = $kernel->handle($request);
		$this->response = $response;
		$kernel->terminate($request, $this->response);
//		$this->restoreSessionsForWordPress();
	}

	public function restoreSessionsForWordPress(): void {
		$middleware = [
			EncryptCookies::class,
			AddQueuedCookiesToResponse::class,
			StartSessionIfAuthenticated::class
		];
		$pipeline = new \Illuminate\Pipeline\Pipeline($this->application);
		$pipeline->send($this->application['request'])
			->through($middleware)
			->then(function() {
				return $this->response;
			});
	}

	/*
	 *
	 */

	protected function normalizeEnvPrefix(): void {
		$prefix = (string)$this->prefixEnv;
		if ($prefix === '') return;
		$len = strlen($prefix);
		foreach (array_keys($_ENV) as $key) {
			if (strpos($key, $prefix) === 0) {
				$plain = substr($key, $len);
				if ($plain === '' || $plain === $key) {
					continue;
				}
				if (strpos($plain, $prefix) === 0) {
					continue;
				}
				$value = $_ENV[$key];
				if (!isset($_ENV[$plain])) $_ENV[$plain] = $value;
				if (!isset($_SERVER[$plain])) $_SERVER[$plain] = $value;
				if (getenv($plain) === false) @putenv("$plain=$value");
			}
		}
	}

}