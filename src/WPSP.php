<?php

namespace WPSPCORE;

use Illuminate\Auth\AuthManager;
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
use Illuminate\Support\Timebox;
use WPSPCORE\App\BaseInstances;
use WPSPCORE\App\Funcs;
use WPSPCORE\App\Http\Middleware\StartSessionIfAuthenticated;

abstract class WPSP extends BaseInstances {

	public $application = null;
	public $response    = null;

	/*
	 *
	 */

	public function setApplication(string $basePath) {
		$commands = $this->getCustomCommands();
		$providers = $this->getConfig('providers');

		$this->application = Application::configure($basePath)
			->withMiddleware(function(Middleware $middleware): void {
				$middleware->append(StartSessionIfAuthenticated::class); // Start session trước mọi code (bao gồm cả view share).
			})
			->withExceptions(function(Exceptions $exceptions): void {})
			->withProviders($providers)
			->withCommands($commands)
			->create();

		$this->bootstrap();
		$this->extends();
		$this->bindings();
		$this->application->boot();
		$this->handleRequest();
	}

	public function setApplicationForConsole(string $basePath) {
		// Load command classes
		$commands = $this->getCustomCommands();

		$this->application = Application::configure($basePath)
			->withMiddleware(function(Middleware $middleware) {})
			->withExceptions(function(Exceptions $exceptions) {})
			->withProviders([])
			->withCommands($commands)
			->create();

		$this->bootstrapConsole();
		$this->extendsConsole();
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

	public function getCustomCommands(): array {
		$commands = $this->funcs->_getAllClassesInDir(
			'WPSPCORE\Console\Commands',
			__DIR__ . '/app/Console/Commands'
		);

		$extendCommands = $this->funcs->_getAllClassesInDir(
			'WPSPCORE\Console\Commands\Extends',
			__DIR__ . '/app/Console/Commands/Extends'
		);

		$commands = array_merge($commands, $extendCommands);

		return $commands;
	}

	public function getConfig($fileName = null) {
		$config = [];

		if ($fileName) {
			$config = require_once __DIR__ . '/config/' . $fileName . '.php';
		}

		return $config;
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

	protected function extends() {
		// Override SessionGuard để thay đổi remember_web_* thành wpsp_remember_web_*
		$this->overrideRememberCookieName();
	}

	protected function extendsConsole() {}

	protected function bindings(): void {
		$this->application->instance('files', new Filesystem());
		$this->application->instance('request', Request::capture());
		$this->application->instance('funcs', $this->funcs ?? new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv, $this->extraParams));
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

	/**
	 * Override SessionGuard để thay đổi remember_web_* thành wpsp_remember_web_*
	 */
	private function overrideRememberCookieName() {
		$this->application->afterResolving('auth', function (AuthManager $auth) {
			$auth->extend('session', function ($app, $name, array $config) use ($auth) {
				$provider = $auth->createUserProvider($config['provider']);

				$guard = new app\Auth\SessionGuard(
					$name,
					$provider,
					$app['session.store'],
					$app['request'],
					$app->make(Timebox::class),
					true,
					200000,
					$app['funcs'] // truyền funcs trực tiếp
				);

				$guard->setCookieJar($app['cookie']);
				$guard->setRequest($app['request']);

				return $guard;
			});
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