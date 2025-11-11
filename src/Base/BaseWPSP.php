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
use Illuminate\Http\Request;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\Store as SessionStore;
use Illuminate\View\View;

abstract class BaseWPSP extends BaseInstances {

	public ?Application $application = null;

	public function setApplication(string $basePath) {
		$app = Application::configure($basePath)
			->withMiddleware(function(Middleware $middleware) {
			})
			->withExceptions(function(Exceptions $exceptions) {
			})
			->withMiddleware(function(Middleware $middleware) {
				$middleware->append(StartSession::class);
			})
			->withProviders()
			->create();

		$this->application = $app;

		$this->bootstrap();
		$this->bindings();
		$this->autoSaveCookie();

		$this->application->boot();
	}

	public function getApplication() {
		return $this->application;
	}

	/*
	 *
	 */

	protected function bootstrap() {
		$application = $this->getApplication();

		// Load environment variables.
		(new LoadEnvironmentVariables)->bootstrap($application);
		$this->normalizeEnvPrefix();

		// Load config & facades.
		(new LoadConfiguration)->bootstrap($application);
		(new RegisterFacades)->bootstrap($application);
		(new RegisterProviders)->bootstrap($application);
	}

	protected function bindings(): void {
		$this->application->singleton('files', function() {
			return new Filesystem();
		});

		$this->application->singleton('request', function() {
			return Request::capture();
		});

		$this->application->singleton('session', function($app) {
			$table      = $this->funcs->_config('session.table');
			$minutes    = $this->funcs->_config('session.lifetime');
			$cookieName = $this->funcs->_config('session.cookie');

			// Use database session handler.
			$connection = $app['db']->connection();
			$handler    = new DatabaseSessionHandler($connection, $table, $minutes, $app);

			// Create the Store
			$store = new SessionStore($cookieName, $handler);

			// If a session cookie exists from the client, re-use its id so we load the same session
			if (!empty($_COOKIE[$cookieName])) {
				try {
					$store->setId($_COOKIE[$cookieName]);
				} catch (\Throwable $e) {
					// ignore invalid id
				}
			}

			// Start the session store (reads payload from handler)
			try {
				$store->start();
			} catch (\Throwable $e) {
				// If start() fails, we still return the store; operations can continue in-memory.
			}

			return $store;
		});

		$this->application->singleton('session.store', function($app) {
			return $app['session'];
		});
	}

	/*
	 *
	 */

	protected function viewShare(): void {
		$view    = $this->application->make('view');
		$request = $this->application->make('request');
		$view->share([
			'wp_user'         => wp_get_current_user(),
			'current_request' => $request,
		]);
	}

	protected function viewCompose(): void {
		$view = $this->application->make('view');
		$view->composer('*', function(View $view) {
			global $notice;
			$view->with('current_view_name', $view->getName());
			$view->with('notice', $notice);
		});
	}

	/*
	 *
	 */

	protected function autoSaveCookie(): void {
		// Ensure the session is saved at the end of the PHP request and the cookie is sent.
		// WordPress does not run a Laravel kernel terminate phase, so we emulate it here.
		register_shutdown_function(function() {
			if (!$this->application->bound('session')) {
				return;
			}

			try {
				$session = $this->application['session'];
				$session->save();

				// Prepare cookie parameters from config
				$cookieName = $this->funcs->_config('session.cookie');
				$lifetime   = (int)$this->funcs->_config('session.lifetime', 120) * 60;
				$path       = $this->funcs->_config('session.path');
				$domain     = $this->funcs->_config('session.domain');
				$secure     = (bool)$this->funcs->_config('session.secure') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
				$httpOnly   = (bool)$this->funcs->_config('session.http_only');
				$sameSite   = $this->funcs->_config('session.same_site');

				// Set the cookie that will be used by the next request to re-load the session
				// Note: setcookie ignores same-site on some PHP versions; we include it when possible.
				if (PHP_VERSION_ID >= 70300) {
					setcookie($cookieName, $session->getId(), [
						'expires'  => time() + $lifetime,
						'path'     => $path,
						'domain'   => $domain ?: null,
						'secure'   => $secure,
						'httponly' => $httpOnly,
						'samesite' => $sameSite,
					]);
				}
				else {
					setcookie($cookieName, $session->getId(), time() + $lifetime, $path, $domain, $secure, $httpOnly);
				}
			}
			catch (\Throwable $e) {
				// Do not let shutdown errors break page rendering
			}
		});
	}

	protected function normalizeEnvPrefix(): void {
		$prefix = $this->prefixEnv;
		foreach ($_ENV as $key => $value) {
			if (strpos($key, $prefix) === 0) {
				$plain = substr($key, strlen($prefix));
				if (!isset($_ENV[$plain])) $_ENV[$plain] = $value;
				if (!isset($_SERVER[$plain])) $_SERVER[$plain] = $value;
				if (getenv($plain) === false) @putenv("$plain=$value");
			}
		}
	}

}