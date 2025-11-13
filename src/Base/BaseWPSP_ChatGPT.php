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
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Broadcasting\BroadcastServiceProvider;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * BaseWPSP - extended to support nearly full Laravel 12 features inside WP:
 * - Auth (session)
 * - Middleware (global & route when using /wpsp/*)
 * - Events & Listeners
 * - FormRequest validation (via container)
 * - Exceptions handling
 * - Broadcasting (basic provider registration)
 * - Queue (driver registration; worker runs externally)
 *
 * Keep method names to match your plugin flow.
 */
abstract class BaseWPSP_ChatGPT extends BaseInstances {

	public ?Application $application = null;

	/**
	 * A simple flag to ensure session restore pipeline runs once per WP request.
	 */
	protected bool $restoredSessionForWP = false;

	/*
	 * Entry point - keep same signature
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

		// Core bootstrap steps
		$this->bootstrap();

		// Bindings (no Request::capture here)
		$this->bindings();

		// Ensure essential providers exist (session, queue, broadcast)
		$this->ensureEssentialProviders();

		// Boot everything (providers, events, listeners)
		$this->application->boot();

		// Prepare exception handling wiring (optional: use app exception handler)
		$this->prepareExceptionHandling();

		// Handle request (will capture Request at the correct time)
		$this->handleRequest();
	}

	public function getApplication($abstract = null, $parameters = []) {
		if ($abstract) {
			return $this->application->make($abstract, $parameters);
		}
		return $this->application;
	}

	/*
	 * Bootstrap: env, config, facades, providers
	 */
	protected function bootstrap() {
		(new LoadEnvironmentVariables)->bootstrap($this->application);
		$this->normalizeEnvPrefix();

		(new LoadConfiguration)->bootstrap($this->application);
		(new RegisterFacades)->bootstrap($this->application);
		(new RegisterProviders)->bootstrap($this->application);
	}

	/*
	 * Basic bindings. Don't capture Request here (too early in WP lifecycle).
	 */
	protected function bindings(): void {
		$this->application->singleton('files', function() {
			return new Filesystem();
		});

		// Other lightweight bindings you might need can go here.
	}

	/*
	 * Ensure providers required for full features are registered.
	 * This will not duplicate providers already registered via config/app.php,
	 * but ensures core capabilities are present.
	 */
	protected function ensureEssentialProviders(): void {
		// Session provider (if not present)
		if (!$this->application->bound('session') && class_exists(SessionServiceProvider::class)) {
			$this->application->register(SessionServiceProvider::class);
		}

		// Queue provider - needed to push jobs; workers run externally.
		if (!$this->application->bound('queue') && class_exists(QueueServiceProvider::class)) {
			$this->application->register(QueueServiceProvider::class);
		}

		// Broadcast provider - register if broadcasting configured
		if (!$this->application->bound('events') && class_exists(BroadcastServiceProvider::class)) {
			// Note: RegisterProviders bootstrap will usually register event/broadcast providers.
			// We attempt to register broadcast provider if not already.
			$this->application->register(BroadcastServiceProvider::class);
		}

		// Logging provider (ensures logger available)
		if (!$this->application->bound('log') && class_exists(LogServiceProvider::class)) {
			$this->application->register(LogServiceProvider::class);
		}
	}

	/**
	 * Main HTTP handling flow.
	 * - Capture request AFTER WP loaded cookie & server headers.
	 * - If Laravel route (/wpsp/*) -> full kernel handle (full middleware stack, routing, CSRF, FormRequest)
	 * - Else (WP request) -> minimal restore: cookies + start session so Auth, FormRequest->validated(), events, etc work in WP
	 *
	 * This method keeps the function names you had but extends behavior to support many Laravel features.
	 */
	protected function handleRequest() {
		// Capture request at correct time (after WP has populated superglobals)
		$request = Request::capture();
		// Bind the request instance into container
		$this->application->instance('request', $request);

		$kernel = $this->application->make(Kernel::class);
		$uri    = $request->getRequestUri();

		// If this is a Laravel-controlled route: run full kernel
		if (str_starts_with($uri, '/web/')) {
			$response = $kernel->handle($request);

			// Rebind request (kernel/middleware may have mutated it)
			$this->application->instance('request', $request);

			// Make sure FormRequests, exceptions, events, queue dispatch behave normally
			$response->send();
			$kernel->terminate($request, $response);
			exit;
		}

		// Normal WordPress request: still allow kernel to run global middleware (if any),
		// then do a minimal restore for session/auth and other features.
		$response = $kernel->handle($request);

		// Rebind request after kernel
//		$this->application->instance('request', $request);

		// Terminate kernel to flush any terminate logic
//		$kernel->terminate($request, $response);

		// Run minimal restore (once) so WP context can use Auth, events, FormRequest validation, etc.
//		$this->restoreSessionForWordPress($request, $response);
		return $response;
	}

	/**
	 * restoreSessionForWordPress
	 *
	 * Keep signature and name, but make it more robust:
	 * - Run minimal middleware pipeline required to restore cookies + session
	 * - Provide a Symfony Response so StartSession can add cookies
	 * - Ensure it runs only once per PHP request
	 *
	 * After this, Auth::user(), event dispatch, FormRequest->validated() (if you manually resolve them),
	 * and dispatching jobs (pushed to queue) will work in WP context.
	 *
	 * Note: Queue workers and broadcast listeners should run as separate processes (artisan queue:work / websocket server).
	 */
	protected function restoreSessionForWordPress($request, $response): void {
		// Idempotent: do this only once per WP request
		if ($this->restoredSessionForWP) {
			return;
		}
		$this->restoredSessionForWP = true;

		$app = $this->application;

		// Provide a real Symfony Response instance (StartSession expects it)
		$symfonyResponse = $response instanceof Response ? $response : new Response();

		// Minimal middleware to restore session & cookies.
		// Do NOT include "web" group or SubstituteBindings; those expect a matched route.
		$middleware = [
			EncryptCookies::class,
			AddQueuedCookiesToResponse::class,
			StartSession::class,
		];

		// Run the pipeline
		(new \Illuminate\Pipeline\Pipeline($app))
			->send($request)
			->through($middleware)
			->then(function() use ($symfonyResponse) {
				// StartSession has run and session is loaded into container.
				// Return the response object to satisfy middleware contracts.
				return $symfonyResponse;
			});

		// After pipeline: session and auth state are restored (if laravel_session cookie present).
		// Now WP hooks (init/template_redirect) can call Auth::user(), dispatch events, resolve FormRequests, etc.
	}

	/**
	 * Prepare exception handler mapping (optional, safe).
	 * If you want to use your Laravel exception handler class, you can set it here.
	 */
	protected function prepareExceptionHandling(): void {
		// If your app has a custom exception handler registered in container at 'App\Exceptions\Handler',
		// Laravel's RegisterProviders should have bound it. Otherwise you can optionally set_exception_handler here.
		// We'll leave default Laravel handler in control, but ensure PHP errors bubble to Laravel logging.
		if (method_exists($this->application, 'make')) {
			try {
				// Bind a simple error handler that logs through Laravel logger
				// (Don't override existing user-level exception handlers)
				if (!function_exists('__wpsp_set_error_handler')) {
					function __wpsp_set_error_handler() {
						\set_error_handler(function($errno, $errstr, $errfile, $errline) {
							if (error_reporting() === 0) return false;
							Log::error("PHP ERROR: [$errno] $errstr in $errfile:$errline");
							return false; // allow regular PHP handler too
						});
					}

					__wpsp_set_error_handler();
				}
			}
			catch (\Throwable $e) {
				// ignore
			}
		}
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
