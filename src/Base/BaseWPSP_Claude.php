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
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

abstract class BaseWPSP_Claude extends BaseInstances {

	public ?Application $application = null;
	protected ?Kernel   $kernel      = null;
	protected bool      $booted      = false;

	/*
	 * Application Setup
	 */

	public function setApplication(string $basePath) {
		$this->application = Application::configure($basePath)
			->withRouting(
				web     : $basePath . '/routes/web.php',
				api     : $basePath . '/routes/api.php',
				commands: $basePath . '/routes/console.php',
				channels: $basePath . '/routes/channels.php',
				health  : '/up',
			)
			->withMiddleware(function(Middleware $middleware): void {})
			->withExceptions(function(Exceptions $exceptions): void {})
			->create();

		$this->bootstrap();
		$this->bindings();
		$this->registerProviders();
		$this->application->boot();
		$this->booted = true;
		$this->handleRequest();
	}

	public function getApplication($abstract = null, $parameters = []) {
		if ($abstract) {
			return $this->application->make($abstract, $parameters);
		}
		return $this->application;
	}

	/*
	 * Bootstrap Process
	 */

	protected function bootstrap() {
		// Load environment variables
		(new LoadEnvironmentVariables)->bootstrap($this->application);
		$this->normalizeEnvPrefix();

		// Load configuration
		(new LoadConfiguration)->bootstrap($this->application);

		// Register facades
		(new RegisterFacades)->bootstrap($this->application);

		// Register providers
		(new RegisterProviders)->bootstrap($this->application);

		// Handle exceptions
		(new HandleExceptions)->bootstrap($this->application);
	}

	protected function bindings(): void {
		// Filesystem
		$this->application->singleton('files', function() {
			return new Filesystem();
		});

		// Request instance
		$this->application->instance('request', Request::capture());

		// Path bindings for WordPress compatibility
//		$this->application->instance('path.public', WP_CONTENT_DIR);
//		$this->application->instance('path.storage', $this->application->storagePath());

		// Custom bindings
		$this->customBindings();
	}

	/**
	 * Register additional service providers
	 */
	protected function registerProviders(): void {
		// Queue Service Provider (for Jobs, Events, Listeners)
		if (!$this->application->providerIsLoaded(\Illuminate\Queue\QueueServiceProvider::class)) {
			$this->application->register(\Illuminate\Queue\QueueServiceProvider::class);
		}

		// Event Service Provider
		if (!$this->application->providerIsLoaded(\Illuminate\Events\EventServiceProvider::class)) {
			$this->application->register(\Illuminate\Events\EventServiceProvider::class);
		}

		// Broadcasting Service Provider
		if (!$this->application->providerIsLoaded(\Illuminate\Broadcasting\BroadcastServiceProvider::class)) {
			$this->application->register(\Illuminate\Broadcasting\BroadcastServiceProvider::class);
		}

		// Notification Service Provider
		if (!$this->application->providerIsLoaded(\Illuminate\Notifications\NotificationServiceProvider::class)) {
			$this->application->register(\Illuminate\Notifications\NotificationServiceProvider::class);
		}

		// Cache Service Provider
		if (!$this->application->providerIsLoaded(\Illuminate\Cache\CacheServiceProvider::class)) {
			$this->application->register(\Illuminate\Cache\CacheServiceProvider::class);
		}
	}

	/**
	 * Custom bindings - override in child class if needed
	 */
	protected function customBindings(): void {
		// Override in child class
	}

	/*
	 * Request Handling
	 */

	protected function handleRequest(): void {
		$request      = $this->application['request'];
		$this->kernel = $this->application->make(Kernel::class);

		// Handle Laravel routes (API, custom routes)
		$uri = $request->getRequestUri();
		if ($this->shouldHandleLaravelRoute($uri)) {
			$response = $this->kernel->handle($request);
			$response->send();
			$this->kernel->terminate($request, $response);
			exit;
		}

		// For WordPress requests, just terminate Laravel kernel
		// but keep session active
		$response = $this->kernel->handle($request);
		$this->kernel->terminate($request, $response);
		$this->restoreSessionForWordPress($request, $response);
	}

	/**
	 * Determine if URI should be handled by Laravel
	 */
	protected function shouldHandleLaravelRoute(string $uri): bool {
		$laravelPrefixes = [
			'/web/',
			'/api/',
			'/broadcasting/',
		];

		foreach ($laravelPrefixes as $prefix) {
			if (str_starts_with($uri, $prefix)) {
				return true;
			}
		}

		return false;
	}

	protected function restoreSessionForWordPress($request, $response): void {
		$middleware = [
			EncryptCookies::class,
			AddQueuedCookiesToResponse::class,
			StartSession::class,
		];

		$pipeline = new Pipeline($this->application);
		$pipeline->send($request)
			->through($middleware)
			->then(function() use ($response) {
				return $response;
			});
	}

	/*
	 * Queue & Jobs Support
	 */

	/**
	 * Dispatch a job to the queue
	 */
	public function dispatch($job): void {
		if ($this->booted) {
			dispatch($job);
		}
	}

	/**
	 * Process queue worker (call this via WP-Cron)
	 */
	public function processQueue(string $queue = 'default', int $maxJobs = 10): void {
		if (!$this->booted) return;

		$worker  = $this->application->make(\Illuminate\Queue\Worker::class);
		$manager = $this->application->make(\Illuminate\Queue\QueueManager::class);

		$connection = $manager->connection();

		for ($i = 0; $i < $maxJobs; $i++) {
			$job = $connection->pop($queue);
			if (!$job) break;

			try {
				$worker->process(
					$connection->getConnectionName(),
					$job,
					new \Illuminate\Queue\WorkerOptions()
				);
			}
			catch (\Throwable $e) {
				$this->application->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
					->report($e);
			}
		}
	}

	/*
	 * Event Support
	 */

	/**
	 * Fire an event
	 */
	public function event($event, $payload = [], bool $halt = false) {
		if ($this->booted) {
			return $this->application['events']->dispatch($event, $payload, $halt);
		}
		return null;
	}

	/**
	 * Register an event listener
	 */
	public function listen($events, $listener): void {
		if ($this->booted) {
			$this->application['events']->listen($events, $listener);
		}
	}

	/*
	 * Utility Methods
	 */

	protected function normalizeEnvPrefix(): void {
		$prefix = (string)$this->prefixEnv;
		if ($prefix === '') return;

		$len = strlen($prefix);
		foreach (array_keys($_ENV) as $key) {
			if (strpos($key, $prefix) === 0) {
				$plain = substr($key, $len);
				if ($plain === '' || $plain === $key) continue;
				if (strpos($plain, $prefix) === 0) continue;

				$value = $_ENV[$key];
				if (!isset($_ENV[$plain])) $_ENV[$plain] = $value;
				if (!isset($_SERVER[$plain])) $_SERVER[$plain] = $value;
				if (getenv($plain) === false) @putenv("$plain=$value");
			}
		}
	}

	/**
	 * Check if application is booted
	 */
	public function isBooted(): bool {
		return $this->booted;
	}

	/**
	 * Get kernel instance
	 */
	public function getKernel(): ?Kernel {
		return $this->kernel;
	}

}