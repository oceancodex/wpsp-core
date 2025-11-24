<?php

namespace WPSPCORE\Base;

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
use WPSPCORE\Funcs;
use WPSPCORE\Http\Middleware\StartSessionIfAuthenticated;

abstract class BaseWPSP extends BaseInstances {

	public $application = null;
	public $response    = null;

	/*
	 *
	 */

	public function setApplication(string $basePath) {
		// Load command classes
		$commands          = $this->funcs->_getAllClassesInDir('WPSPCORE\Console\Commands', __DIR__ . '/../Console/Commands');

		$this->application = Application::configure($basePath)
			->withMiddleware(function(Middleware $middleware): void {
				$middleware->append(StartSessionIfAuthenticated::class); // Start session trước mọi code (bao gồm cả view share).
			})
			->withExceptions(function(Exceptions $exceptions): void {})
			->withCommands($commands)
			->create();

		$this->bootstrap();
		$this->bindings();
		$this->application->boot();
		$this->handleRequest();
	}

	public function setApplicationForConsole(string $basePath) {
		// Load command classes
		$commands = $this->funcs->_getAllClassesInDir(
			'WPSPCORE\Console\Commands',
			__DIR__ . '/../Console/Commands'
		);

		$this->application = Application::configure($basePath)
			->withCommands($commands)
			->withMiddleware(function(Middleware $middleware) {})
			->withExceptions(function(Exceptions $exceptions) {})
			->withProviders([])
			->create();

		$this->bootstrapConsole();
		$this->bindingsConsole();

		return $this->application;
	}

	/*
	 *
	 */

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
		// Environment variables.
		(new LoadEnvironmentVariables)->bootstrap($this->application);

		// Configs.
		(new LoadConfiguration)->bootstrap($this->application);

		// Facades.
		(new RegisterFacades)->bootstrap($this->application);

		// Providers.
		(new RegisterProviders)->bootstrap($this->application);
	}

	protected function bootstrapConsole() {
		(new LoadEnvironmentVariables)->bootstrap($this->application);
		(new LoadConfiguration)->bootstrap($this->application);
	}

	protected function bindings(): void {
		$this->application->instance('files', new Filesystem());
		$this->application->instance('request', Request::capture());
		$this->application->instance('funcs',
			$this->funcs ??
			new Funcs(
				$this->mainPath,
				$this->rootNamespace,
				$this->prefixEnv,
				$this->extraParams
			)
		);
	}

	protected function bindingsConsole() {
		$this->application->instance('files', new Filesystem());
		$this->application->instance('funcs',
			$this->funcs ??
			new Funcs(
				$this->mainPath,
				$this->rootNamespace,
				$this->prefixEnv,
				$this->extraParams
			)
		);
	}

	/*
	 *
	 */

	protected function handleRequest(): void {
		$request        = $this->application['request'];
		$kernel         = $this->application->make(Kernel::class);
		$response       = $kernel->handle($request);
		$this->response = $response;
		$kernel->terminate($request, $this->response);
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

				// Nếu plain rỗng hoặc trùng key => bỏ qua
				if ($plain === '' || $plain === $key) {
					continue;
				}

				// Nếu key dạng PREFIX_something => bỏ qua
				if (strpos($plain, $prefix) === 0) {
					continue;
				}

				$value = $_ENV[$key];

				// Tạo key không prefix
				if (!isset($_ENV[$plain])) $_ENV[$plain] = $value;
				if (!isset($_SERVER[$plain])) $_SERVER[$plain] = $value;
				if (getenv($plain) === false) @putenv("$plain=$value");
			}
		}
	}

}