<?php

namespace WPSPCORE;

use Illuminate\Auth\AuthManager;
use Illuminate\Container\Container;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Timebox;
use WPSPCORE\App\Http\Middleware\WPSPStartSession;
use WPSPCORE\App\View\Directives\adminpagemetaboxes;

abstract class WPSP extends BaseInstances {

	/** @var null|Application|Container */
	public $application = null;
	public $response    = null;

	/*
	 *
	 */

	public function setApplication($basePath, $handleRequest = true) {
		$commands  = $this->getCustomCommands();
		$providers = $this->getConfig('providers');

		$this->application = Application::configure($basePath)
			->withRouting(
				web      : $this->funcs->_getRoutesPath('/original/web.php'),
				api      : $this->funcs->_getRoutesPath('/original/api.php'),
				commands : $this->funcs->_getRoutesPath('/original/console.php'),
				health   : '/up',
//				apiPrefix: 'api/admin',
			)
			->withMiddleware(function(Middleware $middleware) {
//				$middleware->append(StartSessionIfAuthenticated::class); // Start session trước mọi code (bao gồm cả view share).
//				$middleware->append(StartSession::class);
//				$middleware->append(PreventRequestForgery::class);
//				$middleware->append(VerifyCsrfToken::class);
			})
			->withExceptions(function(Exceptions $exceptions) {})
			->withProviders($providers)
			->withCommands($commands)
			->create();

		$this->setPaths();
		$this->afterSetPaths();
		$this->bootstrap();
		$this->afterBoostrap();
		$this->bindings();
		$this->afterBindings();
		$this->extends();

//		$this->registerBladeDirectives();

		$this->application->boot();

		if ($handleRequest) {
			$this->handleRequest();
		}
	}

	public function setApplicationForConsole($basePath) {
		$commands  = $this->getCustomCommands();
		$providers = $this->getConfig('providers');

		$this->application = Application::configure($basePath)
			->withRouting(
				web      : $this->funcs->_getRoutesPath('/original/web.php'),
				api      : $this->funcs->_getRoutesPath('/original/api.php'),
				commands : $this->funcs->_getRoutesPath('/original/console.php'),
				health   : '/up',
//				apiPrefix: 'api/admin',
			)
			->withMiddleware(function(Middleware $middleware) {})
			->withExceptions(function(Exceptions $exceptions) {})
			->withProviders($providers)
			->withCommands($commands)
			->create();

		$this->setPaths();
		$this->afterSetPaths();
		$this->bootstrapConsole();
		$this->afterBoostrapConsole();
		$this->bindingsConsole();
		$this->afterBindingsConsole();
		$this->extendsConsole();

		$this->application->boot();

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

		$integrationCommands = $this->funcs->_getAllClassesInDir(
			$this->funcs->_getRootNamespace() . '\App\Widen\Commands',
			$this->funcs->_getAppPath('/Widen/Commands')
		);

		$commands = array_merge($commands, $extendCommands, $integrationCommands);

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

	public function setPaths() {
		$this->application->useAppPath($this->mainPath . '/app');
		$this->application->useLangPath($this->mainPath . '/lang');
		$this->application->useConfigPath($this->mainPath . '/config');
		$this->application->usePublicPath($this->mainPath . '/public');
		$this->application->useStoragePath($this->mainPath . '/storage');
		$this->application->useDatabasePath($this->mainPath . '/database');
		$this->application->useBootstrapPath($this->mainPath . '/bootstrap');
		$this->application->useEnvironmentPath($this->mainPath);
	}

	/*
	 *
	 */

	public function bootstrap() {
		// Environment variables.
		(new LoadEnvironmentVariables)->bootstrap($this->application);

		// Configs.
		(new LoadConfiguration)->bootstrap($this->application);

		// Facades.
		(new RegisterFacades)->bootstrap($this->application);

		// Providers.
		(new RegisterProviders)->bootstrap($this->application);
	}

	public function bootstrapConsole() {
		// Environment variables.
		(new LoadEnvironmentVariables)->bootstrap($this->application);

		// Configs.
		(new LoadConfiguration)->bootstrap($this->application);

		// Facades.
		(new RegisterFacades)->bootstrap($this->application);

		// Providers.
		(new RegisterProviders)->bootstrap($this->application);
	}

	public function bindings() {
		// Request.
		$this->application->instance(Request::class, $this->request);
		$this->application->instance('request', $this->request);

		// Funcs.
		$this->application->instance('funcs', $this->funcs ?? new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv, $this->extraParams));

		// Files.
		$this->application->singleton('files', function() { return new Filesystem(); });

		// Process.
		$this->application->singleton('process', function($app) { return $app->make(ProcessFactory::class); });

		// Storage và Filesystem.
		$this->application->singleton('filesystem', function($app) { return new FilesystemManager($app); });
		$this->application->alias('filesystem', 'storage');
		$this->application->alias('filesystem', FilesystemManager::class);

		// Exception Renderer Listener — bắt query/log/dump cho trang lỗi.
//		$this->application->singleton(\Illuminate\Foundation\Exceptions\Renderer\Listener::class);
		$this->application->make(\Illuminate\Foundation\Exceptions\Renderer\Listener::class)->registerListeners($this->application->make('events'));
	}

	public function bindingsConsole() {
		// Request.
		$this->application->instance(Request::class, $this->request);
		$this->application->instance('request', $this->request);

		// Funcs.
		$this->application->instance('funcs', $this->funcs ?? new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv, $this->extraParams));

		// Files.
		$this->application->singleton('files', function() { return new Filesystem(); });

		// Process.
		$this->application->singleton('process', function($app) { return $app->make(ProcessFactory::class); });

		// Storage và Filesystem.
		$this->application->singleton('filesystem', function($app) { return new FilesystemManager($app); });
		$this->application->alias('filesystem', 'storage');
		$this->application->alias('filesystem', FilesystemManager::class);
	}

	public function extends() {
		// Override SessionGuard để thay đổi remember_web_* thành wpsp_remember_web_*
		$this->overrideRememberCookieName();
	}

	public function extendsConsole() {}

	/*
	 *
	 */

	public function afterSetPaths() {}

	public function afterBoostrap() {}

	public function afterBoostrapConsole() {}

	public function afterBindings() {}

	public function afterBindingsConsole() {}

	/*
	 *
	 */

	public function registerBladeDirectives() {
		$bladeCompiler = $this->application->make('blade.compiler');

		$directiveClasses = [
			adminpagemetaboxes::class,
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

	public function handleRequest() {
		// Start session.
		$this->startSession();
		$this->saveSession();

		$this->shareErrorsToViews();

		$this->afterHandleRequest();
	}

	public function saveSession() {
		add_action('shutdown', function() {
			// Chặn thêm ở tầng shutdown: không lưu session cho request loopback/CLI
			// kể cả khi vì lý do nào đó session lỡ được start.
			if ($this->funcs->_isWPInternalRequest()) {
				return;
			}

			if (!$this->application->bound('session.store')) {
				return;
			}

			/** @var \Illuminate\Session\Store $session */
			$session = $this->application['session.store'];

			// Chỉ persist khi session đã thực sự start trong request này.
			// Request WP internal (loopback UA "WordPress/...", cron) không start session ở middleware, nên sẽ bị bỏ qua ở đây — không tạo row rác.
			if (!$session->isStarted()) {
				return;
			}

			$sessionManager = $this->application['session'];
			$sessionConfig  = $sessionManager->getSessionConfig();
			$configSession  = $this->funcs->_config('session');

			/**
			 * ---
			 * Save auth cookie session.
			 */
			$session->save();

			$authCookie = cookie(
				$session->getName(),
				$session->getId(),
				$sessionConfig['lifetime'],
				$configSession['path'],
				$configSession['domain'],
				true,
				true,
				false,
				$sessionConfig['same_site']
			);

			@header('Set-Cookie: ' . $authCookie, false);

			/**
			 * ---
			 * Save XSRF token.
			 */
			$encrypter  = $this->application->make(Encrypter::class);
			$xsrfName   = $session->getName().'-XSRF-TOKEN';
			$xsrfPrefix = CookieValuePrefix::create($xsrfName, $encrypter->getKey());
			$xsrfToken  = $encrypter->encrypt(
				$xsrfPrefix.$session->token(),
				EncryptCookies::serialized('XSRF-TOKEN')
			);

			$xsrfCookie = cookie(
				$xsrfName,
				$xsrfToken,
				$sessionConfig['lifetime'],
				$configSession['path'],
				$configSession['domain'],
				$configSession['secure'],
				false,
				false,
				$sessionConfig['same_site']
			);
			@header('Set-Cookie: '.$xsrfCookie, false);
		}, 1);
	}

	public function shareErrorsToViews() {
		if ($this->application->bound('view') && $this->application->bound('session.store')) {
			$errors = $this->application['session.store']->get('errors', new \Illuminate\Support\ViewErrorBag());
			$this->application['view']->share('errors', $errors);
		}
	}

	public function afterHandleRequest() {}

	/*
	 *
	 */

	/**
	 * Start session.
	 */
	public function startSession() {
		if ($this->funcs->_isWPInternalRequest()) {
			return;
		}

		$middleware = $this->application->make(WPSPStartSession::class);
		$middleware->handle($this->request, function($request) {
			return $request;
		}, ['funcs' => $this->funcs]);
	}

	/**
	 * Override SessionGuard để thay đổi remember_web_* thành wpsp_remember_web_*
	 */
	private function overrideRememberCookieName() {
		$this->application->afterResolving('auth', function(AuthManager $auth) {
			$auth->extend('session', function($app, $name, $config) use ($auth) {
				$provider = $auth->createUserProvider($config['provider']);

				$guard = new \WPSPCORE\App\Auth\SessionGuard(
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

}