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
use WPSPCORE\App\Http\Middleware\StartSessionIfAuthenticated;
use WPSPCORE\App\View\Directives\adminpagemetaboxes;

abstract class WPSP extends BaseInstances {

	public $application = null;
	public $response    = null;

	/*
	 *
	 */

	public function setApplication($basePath) {
		$commands = $this->getCustomCommands();
		$providers = $this->getConfig('providers');

		$this->application = Application::configure($basePath)
			->withMiddleware(function(Middleware $middleware) {
				$middleware->append(StartSessionIfAuthenticated::class); // Start session trước mọi code (bao gồm cả view share).
			})
			->withExceptions(function(Exceptions $exceptions) {})
			->withProviders($providers)
			->withCommands($commands)
			->create();

		$this->bootstrap();
		$this->extends();
		$this->bindings();

//		$this->registerBladeDirectives();

		$this->application->boot();

		$this->handleRequest();
		$this->afterHandleRequest();
	}

	public function setApplicationForConsole($basePath) {
		$commands = $this->getCustomCommands();
		$providers = $this->getConfig('providers');

		$this->application = Application::configure($basePath)
			->withMiddleware(function(Middleware $middleware) {})
			->withExceptions(function(Exceptions $exceptions) {})
			->withProviders($providers)
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

	public function getCustomCommands() {
		$commands = $this->funcs->_getAllClassesInDir(
			'WPSPCORE\App\Console\Commands',
			__DIR__ . '/app/Console/Commands'
		);

		$extendCommands = $this->funcs->_getAllClassesInDir(
			'WPSPCORE\App\Console\Commands\Extends',
			__DIR__ . '/app/Console/Commands/Extends'
		);

		$commands = array_merge($commands, $extendCommands);

		return $commands;
	}

	public function getConfig($fileName = null) {
		$config = [];

		if ($fileName) {
			$config = require __DIR__ . '/config/' . $fileName . '.php';
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
		// Environment variables.
		(new LoadEnvironmentVariables)->bootstrap($this->application);

		// Configs.
		(new LoadConfiguration)->bootstrap($this->application);

		// Facades.
		(new RegisterFacades)->bootstrap($this->application);

		// Providers.
		(new RegisterProviders)->bootstrap($this->application);
	}

	protected function extends() {
		// Override SessionGuard để thay đổi remember_web_* thành wpsp_remember_web_*
		$this->overrideRememberCookieName();
	}

	protected function extendsConsole() {}

	protected function bindings() {
		$this->application->instance('files', new Filesystem());
		$this->application->instance('request', Request::capture());
		$this->application->instance('funcs', $this->funcs ?? new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv, $this->extraParams));
	}

	protected function bindingsConsole() {
		$this->application->instance('files', new Filesystem());
		$this->application->instance('funcs', $this->funcs ?? new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv, $this->extraParams));
	}

	protected function registerBladeDirectives() {
		$bladeCompiler = $this->application->make('blade.compiler');

		$directiveClasses = [
			adminpagemetaboxes::class
		];

		foreach ($directiveClasses as $directiveClass) {
			(new $directiveClass(
				$this->mainPath,
				$this->rootNamespace,
				$this->prefixEnv,
				array_merge($this->extraParams, ['funcs' => $this->funcs])
			))->register($bladeCompiler);
		}
	}

	/*
	 *
	 */

	protected function handleRequest() {
		$request        = $this->application['request'];
		$kernel         = $this->application->make(Kernel::class);
		$response       = $kernel->handle($request);
		$this->response = $response;
		$kernel->terminate($request, $this->response);
	}

	protected function afterHandleRequest() {
		// Share flash data to view.
		add_action('template_redirect', function() {
//			$this->application->make('view')->share('errors', session('errors'));
			$this->application->booted(function ($app) {
				$session = $app['session.store'];
				$view    = $app['view'];

				foreach ($session->get('_flash.new', []) as $key) {
					$view->share($key, $session->get($key));
				}
			});
		});
	}

	/*
	 *
	 */

	/**
	 * Override SessionGuard để thay đổi remember_web_* thành wpsp_remember_web_*
	 */
	private function overrideRememberCookieName() {
		$this->application->afterResolving('auth', function (AuthManager $auth) {
			$auth->extend('session', function ($app, $name, $config) use ($auth) {
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

	protected function normalizeEnvPrefix() {
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