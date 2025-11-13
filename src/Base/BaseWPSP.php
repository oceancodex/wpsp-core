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
		if (str_starts_with($uri, '/web/')) {
			$response->send();
			$kernel->terminate($request, $response);
			exit;
		}
		$this->response = $response;
	}

	public function restoreSessionsForWordPress(): void {
		var_dump(defined('WPSP_IS_REWRITE_FRONT_PAGE'));
		if (
			is_admin()
			|| (defined('REST_REQUEST') && REST_REQUEST === true)
			|| (defined('DOING_AJAX') && DOING_AJAX === true)
			|| (defined('DOING_CRON') && DOING_CRON === true)
			|| is_front_page()
			|| is_page()
		) {
			error_log($this->application['request']->getRequestUri());
			$middleware = [EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSessionIfAuthenticated::class];
			$pipeline = new \Illuminate\Pipeline\Pipeline($this->application);
			$pipeline->send($this->application['request'])
				->through($middleware)
				->then(function() {
					return $this->response;
				});
		}
	}

	/*
	 *
	 */

	protected function autoSaveCookie(): void {
		// Ensure the session is saved at the end of the PHP request and the cookie is sent.
		// WordPress does not run a Laravel kernel terminate phase, so we emulate it here.
		if (!$this->application->bound('session')) {
			return;
		}

//		try {
			$session = $this->application['session'];
//			$session->save();

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
//		}
//		catch (\Throwable $e) {
//			// Do not let shutdown errors break page rendering
//		}
	}

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