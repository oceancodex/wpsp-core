<?php

namespace WPSPCORE\Base;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
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

	/*
	 *
	 */

	public function setApplication(string $basePath) {
		$app = Application::configure($basePath)
			->withMiddleware(function(Middleware $middleware) {
				$middleware->append(StartSession::class);
				$middleware->append(AddQueuedCookiesToResponse::class);
			})
			->withExceptions(function(Exceptions $exceptions) {
			})
			->withProviders()
			->create();

		$this->application = $app;

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
		// Files.
		$this->application->singleton('files', function() {
			return new Filesystem();
		});
		// Request.
		$this->application->singleton('request', function() {
			return Request::capture();
		});
		// Session.
		$this->application->singleton('session', function($app) {
			$config = $app['config']->get('session', []);

			// Tạo SessionManager trực tiếp (không thông qua $app->make('session'))
			$managerClass = \Illuminate\Session\SessionManager::class;

			// Nếu class SessionManager không tồn tại thì có gì đó sai với autoload. Throw để debug.
			if (! class_exists($managerClass)) {
				throw new \RuntimeException('SessionManager class not found. Check composer autoload and providers.');
			}

			/** @var \Illuminate\Session\SessionManager $manager */
			$manager = new $managerClass($app);

			$driver = $config['driver'] ?? 'database';

			// driver() có thể dùng container internals nhưng không gọi make('session')
			$store = $manager->driver($driver);

			// Khôi phục session id từ cookie nếu có
			$cookieName = $config['cookie'] ?? $this->funcs->_getAppShortName() . '-session';
			if (!empty($_COOKIE[$cookieName])) {
				try {
					$store->setId($_COOKIE[$cookieName]);
				} catch (\Throwable $e) {
					// ignore invalid id
				}
			}

			try {
				$store->start();
			} catch (\Throwable $e) {
				// safe fallback: không crash toàn bộ app
			}

			return $store;
		});
		// Session store.
		$this->application->singleton('session.store', function($app) {
			return $app['session'];
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