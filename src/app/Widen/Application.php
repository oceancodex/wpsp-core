<?php
/**
 * Created by PhpStorm.
 * User: Khanh
 * Date: 05/10/2026
 * Time: 8:56 CH
 */

namespace WPSPCORE\App\Widen;

use WPSPCORE\App\Widen\Http\Request;
use WPSPCORE\App\Widen\Support\Facades\Facade;

/**
 * Application - mô phỏng Illuminate\Foundation\Application bằng PHP thuần.
 *
 * - Container đầy đủ (kế thừa Container).
 * - Bindings sẵn có: 'app', 'request', 'commands', 'path.*'.
 * - Paths, environment, locale, service providers (kể cả deferred), boot/terminate.
 * - Cấu hình fluent: Application::configure($basePath)->withProviders([...])->withSingletons([...])->create().
 *
 * Service provider: bất kỳ class nào có register() và/hoặc boot(); thuộc tính public
 * $bindings / $singletons; deferred khi có provides() và isDeferred() === true.
 */
class Application extends Container {

	public $name    = 'WPSP Framework';
	public $version = 'Lite';

	protected $basePath;

	/** name => đường dẫn tuyệt đối do useXPath() đặt; mặc định tính theo basePath. */
	protected $paths = [];

	protected $defaultPaths = [
		'app'       => 'app',
		'bootstrap' => 'bootstrap',
		'config'    => 'config',
		'database'  => 'database',
		'lang'      => 'lang',
		'public'    => 'public',
		'resources' => 'resources',
		'storage'   => 'storage',
	];

	protected $environment;
	protected $locale;

	protected $booted               = false;
	protected $bootingCallbacks     = [];
	protected $bootedCallbacks      = [];
	protected $terminatingCallbacks = [];

	/** class => provider */
	protected $serviceProviders = [];

	/** class => true */
	protected $loadedProviders = [];

	/** service => provider (object|class) */
	protected $deferredServices = [];

	public function __construct($basePath = null) {
		if ($basePath) {
			$this->setBasePath($basePath);
		}

		$this->registerBaseBindings();
		$this->registerCoreContainerAliases();
	}

	/*
	 * ---
	 * Configure (giống bootstrap/app.php của Laravel 11+).
	 * ---
	 */

	public static function configure($basePath = null) {
		return new static($basePath ?? getcwd());
	}

	public function withProviders(array $providers) {
		foreach ($providers as $provider) {
			$this->register($provider);
		}
		return $this;
	}

	public function withBindings(array $bindings) {
		foreach ($bindings as $abstract => $concrete) {
			is_int($abstract) ? $this->bind($concrete) : $this->bind($abstract, $concrete);
		}
		return $this;
	}

	public function withSingletons(array $singletons) {
		foreach ($singletons as $abstract => $concrete) {
			is_int($abstract) ? $this->singleton($concrete) : $this->singleton($abstract, $concrete);
		}
		return $this;
	}

	public function withInstances(array $instances) {
		foreach ($instances as $abstract => $instance) {
			$this->instance($abstract, $instance);
		}
		return $this;
	}

	/**
	 * Đăng ký command: thư mục, tên class hoặc object command.
	 * Gọi không tham số => nạp app/Console/Commands (giống Laravel).
	 */
	public function withCommands(array $commands = []) {
		$commands = $commands ?: [$this->path('Console/Commands')];

		if ($this->resolved('commands')) {
			$this->make('commands')->register($commands);
		}
		else {
			$this->afterResolving('commands', function(Commands $kernel) use ($commands) {
				$kernel->register($commands);
			});
		}
		return $this;
	}

	public function create() {
		return $this;
	}

	/*
	 * ---
	 * Base bindings.
	 * ---
	 */

	protected function registerBaseBindings() {
		static::setInstance($this);
		Facade::setFacadeApplication($this);

		$this->instance('app', $this);

		$this->singleton('request', function() {
			return Request::capture();
		});

		$this->singleton('commands', function($app) {
			return new Commands($app);
		});

		$this->registerRequestSubclassResolving();
	}

	/**
	 * Type-hint class con của Request (vd: Facades\Request, FormRequest) => container
	 * sẽ build object rỗng. Callback này đổ dữ liệu của request hiện tại vào object đó
	 * (query, post, server, headers, files, session, route/user resolver...), giống
	 * cách Laravel xử lý FormRequest.
	 */
	protected function registerRequestSubclassResolving() {
		$this->resolving(function($object, $app) {
			$isRequest = $object instanceof Request
				|| (class_exists('Illuminate\Http\Request', false) && $object instanceof \Illuminate\Http\Request);

			if (!$isRequest) {
				return;
			}

			$current = $app->make('request');

			if ($object !== $current) {
				get_class($object)::createFrom($current, $object);
			}
		});
	}

	protected function registerCoreContainerAliases() {
		$aliases = [
			'app'      => array_unique([self::class, static::class, Container::class]),
			'request'  => [Request::class, 'Illuminate\Http\Request', 'Symfony\Component\HttpFoundation\Request'],
			'commands' => [Commands::class],
		];

		foreach ($aliases as $key => $list) {
			foreach ($list as $alias) {
				$this->alias($key, $alias);
			}
		}
	}

	public function version() {
		return $this->version;
	}

	/*
	 * ---
	 * Paths.
	 * ---
	 */

	public function setBasePath($basePath) {
		$this->basePath = rtrim($basePath, '\/');
		$this->bindPathsInContainer();
		return $this;
	}

	protected function bindPathsInContainer() {
		$this->instance('path', $this->path());
		$this->instance('path.base', $this->basePath());

		foreach (array_keys($this->defaultPaths) as $name) {
			if ($name !== 'app') {
				$this->instance('path.' . $name, $this->namedPath($name));
			}
		}
	}

	protected function namedPath($name, $path = '') {
		$base = $this->paths[$name] ?? $this->basePath($this->defaultPaths[$name]);
		return $this->joinPaths($base, $path);
	}

	protected function useNamedPath($name, $path) {
		$this->paths[$name] = rtrim($path, '\/');
		$this->instance($name === 'app' ? 'path' : 'path.' . $name, $this->paths[$name]);
		return $this;
	}

	public function joinPaths($basePath, $path = '') {
		return $path !== '' && $path !== null ? $basePath . DIRECTORY_SEPARATOR . ltrim($path, '\/') : $basePath;
	}

	public function basePath($path = '') {
		return $this->joinPaths((string)$this->basePath, $path);
	}

	public function path($path = '') {
		return $this->namedPath('app', $path);
	}

	public function bootstrapPath($path = '') {
		return $this->namedPath('bootstrap', $path);
	}

	public function configPath($path = '') {
		return $this->namedPath('config', $path);
	}

	public function databasePath($path = '') {
		return $this->namedPath('database', $path);
	}

	public function langPath($path = '') {
		return $this->namedPath('lang', $path);
	}

	public function publicPath($path = '') {
		return $this->namedPath('public', $path);
	}

	public function resourcePath($path = '') {
		return $this->namedPath('resources', $path);
	}

	public function storagePath($path = '') {
		return $this->namedPath('storage', $path);
	}

	public function useAppPath($path) {
		return $this->useNamedPath('app', $path);
	}

	public function useBootstrapPath($path) {
		return $this->useNamedPath('bootstrap', $path);
	}

	public function useConfigPath($path) {
		return $this->useNamedPath('config', $path);
	}

	public function useDatabasePath($path) {
		return $this->useNamedPath('database', $path);
	}

	public function useLangPath($path) {
		return $this->useNamedPath('lang', $path);
	}

	public function usePublicPath($path) {
		return $this->useNamedPath('public', $path);
	}

	public function useStoragePath($path) {
		return $this->useNamedPath('storage', $path);
	}

	/*
	 * ---
	 * Environment.
	 * ---
	 */

	/**
	 * environment() => tên môi trường; environment('local', 'dev*') => bool.
	 */
	public function environment(...$environments) {
		$env = $this->environment ??= $this->detectDefaultEnvironment();

		if (!$environments) {
			return $env;
		}

		$patterns = is_array($environments[0]) ? $environments[0] : $environments;
		foreach ($patterns as $pattern) {
			if ($pattern === $env || fnmatch($pattern, $env)) {
				return true;
			}
		}
		return false;
	}

	public function detectEnvironment(\Closure $callback) {
		return $this->environment = (string)$callback();
	}

	protected function detectDefaultEnvironment() {
		if (($env = getenv('APP_ENV')) !== false && $env !== '') {
			return $env;
		}
		if (function_exists('wp_get_environment_type')) {
			return wp_get_environment_type();
		}
		return 'production';
	}

	public function isLocal() {
		return $this->environment('local');
	}

	public function isProduction() {
		return $this->environment('production');
	}

	public function runningInConsole() {
		$env = getenv('APP_RUNNING_IN_CONSOLE');
		return $env !== false ? filter_var($env, FILTER_VALIDATE_BOOLEAN) : in_array(PHP_SAPI, ['cli', 'phpdbg'], true);
	}

	public function runningUnitTests() {
		return $this->environment('testing');
	}

	public function hasDebugModeEnabled() {
		$env = getenv('APP_DEBUG');
		if ($env !== false) {
			return filter_var($env, FILTER_VALIDATE_BOOLEAN);
		}
		return defined('WP_DEBUG') && WP_DEBUG;
	}

	/*
	 * ---
	 * Locale.
	 * ---
	 */

	public function getLocale() {
		return $this->locale ?? (function_exists('get_locale') ? get_locale() : 'en');
	}

	public function currentLocale() {
		return $this->getLocale();
	}

	public function setLocale($locale) {
		$this->locale = $locale;

		if ($this->resolved('request')) {
			$this->make('request')->setLocale($locale);
		}
	}

	public function isLocale($locale) {
		return $this->getLocale() === $locale;
	}

	/*
	 * ---
	 * Service providers.
	 * ---
	 */

	/**
	 * Đăng ký provider (class name hoặc object). Trả về instance provider.
	 */
	public function register($provider, $force = false) {
		if (($registered = $this->getProvider($provider)) && !$force) {
			return $registered;
		}

		if (is_string($provider)) {
			$provider = $this->resolveProvider($provider);
		}

		if ($this->isDeferredProvider($provider) && !$force) {
			$this->addDeferredProvider($provider);
			return $provider;
		}

		if (method_exists($provider, 'register')) {
			$provider->register();
		}

		foreach (['bindings' => false, 'singletons' => true] as $property => $shared) {
			if (isset($provider->{$property}) && is_array($provider->{$property})) {
				foreach ($provider->{$property} as $key => $value) {
					is_int($key) ? $this->bind($value, null, $shared) : $this->bind($key, $value, $shared);
				}
			}
		}

		$this->markAsRegistered($provider);

		if ($this->isBooted()) {
			$this->bootProvider($provider);
		}

		return $provider;
	}

	public function resolveProvider($provider) {
		return new $provider($this);
	}

	public function getProvider($provider) {
		$name = is_string($provider) ? ltrim($provider, '\\') : get_class($provider);
		return $this->serviceProviders[$name] ?? null;
	}

	public function getProviders($provider) {
		return array_values(array_filter($this->serviceProviders, function($value) use ($provider) {
			return $value instanceof $provider;
		}));
	}

	public function getLoadedProviders() {
		return $this->loadedProviders;
	}

	public function providerIsLoaded($provider) {
		return isset($this->loadedProviders[ltrim($provider, '\\')]);
	}

	protected function markAsRegistered($provider) {
		$class = get_class($provider);

		$this->serviceProviders[$class] = $provider;
		$this->loadedProviders[$class]  = true;
	}

	protected function isDeferredProvider($provider) {
		if (!method_exists($provider, 'provides')) {
			return false;
		}
		if (interface_exists('Illuminate\Contracts\Support\DeferrableProvider') && $provider instanceof \Illuminate\Contracts\Support\DeferrableProvider) {
			return true;
		}
		return method_exists($provider, 'isDeferred') && $provider->isDeferred();
	}

	protected function addDeferredProvider($provider) {
		foreach ((array)$provider->provides() as $service) {
			$this->deferredServices[$service] = $provider;
		}
	}

	public function getDeferredServices() {
		return $this->deferredServices;
	}

	public function addDeferredServices(array $services) {
		$this->deferredServices = array_merge($this->deferredServices, $services);
	}

	public function isDeferredService($service) {
		return isset($this->deferredServices[$service]);
	}

	public function loadDeferredProviders() {
		foreach (array_keys($this->deferredServices) as $service) {
			$this->loadDeferredProvider($service);
		}
	}

	public function loadDeferredProvider($service) {
		if (!$this->isDeferredService($service)) {
			return;
		}

		$provider = $this->deferredServices[$service];

		// Một provider có thể cung cấp nhiều service => gỡ hết trước khi đăng ký.
		$this->deferredServices = array_filter($this->deferredServices, function($value) use ($provider) {
			return $value !== $provider;
		});

		if (!$this->getProvider($provider)) {
			$this->register($provider, true);
		}
	}

	public function make($abstract, array $parameters = []) {
		$abstract = $this->getAlias($abstract);

		if (isset($this->deferredServices[$abstract]) && !isset($this->instances[$abstract])) {
			$this->loadDeferredProvider($abstract);
		}

		return parent::make($abstract, $parameters);
	}

	public function bound($abstract) {
		return $this->isDeferredService($abstract) || parent::bound($abstract);
	}

	/*
	 * ---
	 * Boot / terminate.
	 * ---
	 */

	public function isBooted() {
		return $this->booted;
	}

	public function boot() {
		if ($this->booted) {
			return;
		}

		$this->fireAppCallbacks($this->bootingCallbacks);

		foreach ($this->serviceProviders as $provider) {
			$this->bootProvider($provider);
		}

		$this->booted = true;

		$this->fireAppCallbacks($this->bootedCallbacks);
	}

	protected function bootProvider($provider) {
		if (method_exists($provider, 'boot')) {
			$this->call([$provider, 'boot']);
		}
	}

	public function booting(callable $callback) {
		$this->bootingCallbacks[] = $callback;
		return $this;
	}

	public function booted(callable $callback) {
		$this->bootedCallbacks[] = $callback;

		if ($this->isBooted()) {
			$callback($this);
		}
		return $this;
	}

	protected function fireAppCallbacks(array &$callbacks) {
		$index = 0;
		while ($index < count($callbacks)) {
			$callbacks[$index]($this);
			$index++;
		}
	}

	public function terminating($callback) {
		$this->terminatingCallbacks[] = $callback;
		return $this;
	}

	public function terminate() {
		$index = 0;
		while ($index < count($this->terminatingCallbacks)) {
			$this->call($this->terminatingCallbacks[$index]);
			$index++;
		}
	}

	/*
	 * ---
	 * Console.
	 * ---
	 */

	/**
	 * Chạy CLI: exit($app->handleCommand());
	 */
	public function handleCommand(?array $argv = null) {
		$this->boot();

		$status = $this->make('commands')->run($argv);

		$this->terminate();

		return $status;
	}

	/*
	 * ---
	 * Flush.
	 * ---
	 */

	public function flush() {
		parent::flush();

		$this->paths                = [];
		$this->bootingCallbacks     = [];
		$this->bootedCallbacks      = [];
		$this->terminatingCallbacks = [];
		$this->serviceProviders     = [];
		$this->loadedProviders      = [];
		$this->deferredServices     = [];
		$this->booted               = false;
	}

}