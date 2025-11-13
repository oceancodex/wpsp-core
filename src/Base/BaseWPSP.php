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
use Illuminate\Http\Response;
use WPSPCORE\Http\Middleware\StartSessionIfAuthenticated;

abstract class BaseWPSP extends BaseInstances {

	public ?Application $application = null;
	public ?\Symfony\Component\HttpFoundation\Response $response = null;

	/*
	 *
	 */

	public function setApplication(string $basePath) {
		$this->application = Application::configure($basePath)
			->withRouting(
				web     : $basePath . '/routes/web.php',
				commands: $basePath . '/routes/console.php',
				health  : '/up',
			)
			->withMiddleware(function(Middleware $middleware): void {})
			->withExceptions(function(Exceptions $exceptions): void {})
			->create();
		$this->bootstrap();
		$this->bindings();
		$this->application->boot();
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
		$this->application->singleton('files', function() {
			return new Filesystem();
		});
		$this->application->instance('request', Request::capture());
	}

	protected function handleRequest(): void {
		$request = $this->application['request'];
//		$request = Request::capture();
//		$this->application->instance('request', $request);

		$kernel = $this->application->make(Kernel::class);
		$response = $kernel->handle($request);

		$uri = $request->getRequestUri();
		if (strpos($uri, '/web/') === 0) {
			$response->send();
			$kernel->terminate($request, $response);
			exit;
		}

		$this->response = $response ?? new Response();
		$this->response->setStatusCode(200);

		$kernel->terminate($request, $this->response);

		$this->restoreSessionsForWordPress();
	}

	public function restoreSessionsForWordPress(): void {
		$middleware = [EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSessionIfAuthenticated::class];
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
		// iterate keys snapshot to avoid modification-while-iterating issues
		foreach (array_keys($_ENV) as $key) {
			if (strpos($key, $prefix) === 0) {
				$plain = substr($key, $len);
				// guard: avoid empty or same-key loops
				if ($plain === '' || $plain === $key) {
					continue;
				}
				// also avoid if plain still begins with prefix (prevents repeated stripping)
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