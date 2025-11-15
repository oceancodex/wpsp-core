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
use Illuminate\Session\Middleware\StartSession;
use WPSPCORE\Console\Commands\MakeAdminPageCommand;
use WPSPCORE\Funcs;
use WPSPCORE\Http\Middleware\StartSessionIfAuthenticated;

abstract class BaseWPSP extends BaseInstances {

	public $application = null;
	public $response = null;

	/*
	 *
	 */

	public function setApplication(string $basePath) {
		$this->application = Application::configure($basePath)
			->withMiddleware(function(Middleware $middleware): void {
				$middleware->append(StartSession::class);
			})
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
		$this->application->instance('funcs', $this->funcs ?? new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv, $this->extraParams));
	}

	protected function handleRequest(): void {
		$request        = $this->application['request'];
		$kernel         = $this->application->make(Kernel::class);
		$response = $kernel->handle($request);
		$this->response = $response;
		$kernel->terminate($request, $this->response);
		$this->restoreSessionsForWordPress($request);
	}

	public function restoreSessionsForWordPress($request): void {
		register_shutdown_function(function() use ($request) {
			$clientCookie = $request->cookie('wpsp-session');
			$session = app('session');
			if ($clientCookie) {
				$session->setId($clientCookie);
				$session->save();
			}
			else {
				$session->save();
				$lifetime = config('session.lifetime', 120); // 120 phút
				$cookie = cookie(
					$session->getName(),
					$session->getId(),
					$lifetime,
					'/',
					null,
					false,
					true,
					false,
					'Lax'
				);
				$response = new Response();
				$response->headers->setCookie($cookie);
				$response->sendHeaders();
			}
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