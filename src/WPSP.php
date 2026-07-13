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
use Illuminate\Foundation\Exceptions\Renderer\Listener as ExceptionRendererListener;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Support\Timebox;
use WPSPCORE\App\Http\Middleware\WPSPStartSession;
use WPSPCORE\App\View\Directives\adminpagemetaboxes;

abstract class WPSP extends BaseInstances {

	/** @var null|Application|Container */
	public $application = null;
	public $response    = null;

	/*
	 * Bootstrap
	 */

	public function setApplication($basePath, $handleRequest = true) {
		$this->buildApplication($basePath);

		$this->setPaths();
		$this->afterSetPaths();
		$this->bootstrap();
		$this->afterBoostrap();
		$this->bindings();
		$this->afterBindings();
		$this->extends();

		$this->application->boot();

		if ($handleRequest) {
			$this->handleRequest();
		}
	}

	public function setApplicationForConsole($basePath) {
		$this->buildApplication($basePath);

		$this->setPaths();
		$this->afterSetPaths();
		$this->bootstrap();          // logic giống hệt web, không cần bản Console riêng
		$this->afterBoostrapConsole();
		$this->bindingsBase();       // console không cần Listener của exception renderer
		$this->afterBindingsConsole();
		$this->extendsConsole();

		$this->application->boot();

		return $this->application;
	}

	private function buildApplication($basePath): void {
		$this->application = Application::configure($basePath)
			->withRouting(
				web     : $this->funcs->_getRoutesPath('/original/web.php'),
				api     : $this->funcs->_getRoutesPath('/original/api.php'),
				commands: $this->funcs->_getRoutesPath('/original/console.php'),
				health  : '/up',
			)
			->withMiddleware(function(Middleware $middleware) {})
			->withExceptions(function(Exceptions $exceptions) {})
			->withProviders($this->getConfig('providers'))
			->withCommands($this->getCustomCommands())
			->create();
	}

	/*
	 * Getters
	 */

	public function getApplication($abstract = null, $parameters = []) {
		return $abstract
			? $this->application->make($abstract, $parameters)
			: $this->application;
	}

	public function getCustomCommands() {
		return array_merge(
			$this->funcs->_getAllClassesInDir(
				'WPSPCORE\App\Console\Commands',
				__DIR__.'/app/Console/Commands'
			),
			$this->funcs->_getAllClassesInDir(
				'WPSPCORE\App\Console\Commands\Extends',
				__DIR__.'/app/Console/Commands/Extends'
			),
			$this->funcs->_getAllClassesInDir(
				$this->funcs->_getRootNamespace().'\App\Widen\Commands',
				$this->funcs->_getAppPath('/Widen/Commands')
			),
		);
	}

	public function getConfig($fileName = null) {
		return $fileName ? require __DIR__.'/config/'.$fileName.'.php' : [];
	}

	/*
	 * Paths
	 */

	public function setPaths() {
		$this->application->useAppPath($this->mainPath.'/app');
		$this->application->useLangPath($this->mainPath.'/lang');
		$this->application->useConfigPath($this->mainPath.'/config');
		$this->application->usePublicPath($this->mainPath.'/public');
		$this->application->useStoragePath($this->mainPath.'/storage');
		$this->application->useDatabasePath($this->mainPath.'/database');
		$this->application->useBootstrapPath($this->mainPath.'/bootstrap');
		$this->application->useEnvironmentPath($this->mainPath);
	}

	/*
	 * Bootstrap / Bindings
	 */

	public function bootstrap() {
		(new LoadEnvironmentVariables)->bootstrap($this->application);
		(new LoadConfiguration)->bootstrap($this->application);
		(new RegisterFacades)->bootstrap($this->application);
		(new RegisterProviders)->bootstrap($this->application);
	}

	// Alias giữ lại để không phá vỡ code cũ gọi bootstrapConsole().
	public function bootstrapConsole() {
		$this->bootstrap();
	}

	/**
	 * Bindings dùng chung cho cả web & console.
	 */
	private function bindingsBase(): void {
		$this->application->instance('request', $this->request);

		$this->application->instance(
			'funcs',
			$this->funcs ?? new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv, $this->extraParams)
		);

		$this->application->singleton('files', fn() => new Filesystem());

		$this->application->singleton('process', fn($app) => $app->make(ProcessFactory::class));

		$this->application->singleton('filesystem', fn($app) => new FilesystemManager($app));
		$this->application->alias('filesystem', 'storage');
		$this->application->alias('filesystem', FilesystemManager::class);
	}

	/**
	 * instance - khởi tạo ngay khi bootstrap.
	 * singleton - chỉ khởi tạo khi cần.
	 */
	public function bindings() {
		$this->bindingsBase();

		// Exception Renderer Listener — bắt query/log/dump cho trang lỗi.
		// Bind singleton TRƯỚC khi make để renderer và listener share cùng instance
		// (nếu không, Queries tab sẽ không xuất hiện).
		$this->application->singleton(ExceptionRendererListener::class);
		$this->application->make(ExceptionRendererListener::class)
			->registerListeners($this->application->make('events'));
	}

	// Alias giữ lại tương thích ngược.
	public function bindingsConsole() {
		$this->bindingsBase();
	}

	public function extends() {
		$this->overrideRememberCookieName();
	}

	public function extendsConsole() {}

	/*
	 * Hooks
	 */

	public function afterSetPaths() {}

	public function afterBoostrap() {}

	public function afterBoostrapConsole() {}

	public function afterBindings() {}

	public function afterBindingsConsole() {}

	public function afterHandleRequest() {}

	/*
	 * Blade directives
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
	 * Request lifecycle
	 */

	public function handleRequest() {
		$this->startSession();

		// 1: Đẩy Cookie sớm về Client.
		$this->sendSessionCookiesToClient();

		// 2: Bật Output Buffering để đánh chặn TẤT CẢ các lệnh die/exit (bao gồm cả wp_send_json)
		ob_start(function($buffer) {
			// Hàm này tự động chạy NGAY TRƯỚC KHI PHP kết thúc request (kể cả khi gọi die/exit)
			$this->saveSession();
			return $buffer;
		});

		// 3: Dự phòng cho request thông thường kết thúc qua hook shutdown của WP.
		if (function_exists('add_action')) {
			add_action('shutdown', [$this, 'saveSession'], 1);
		} else {
			register_shutdown_function([$this, 'saveSession']);
		}

		$this->shareErrorsToViews();

		$this->afterHandleRequest();
	}

	public function startSession() {
		if ($this->funcs->_isWPInternalRequest()) {
			return;
		}

		$middleware = $this->application->make(WPSPStartSession::class);
		$middleware->handle($this->request, fn($request) => $request, ['funcs' => $this->funcs]);
	}

	public function sendSessionCookiesToClient() {
		$session = $this->resolveStartedSession();
		if (!$session) {
			return;
		}

		$this->emitCookies($this->buildSessionCookies($session));
	}

	public function saveSession() {
		// Chặn ở tầng shutdown: không lưu session cho request loopback/CLI,
		// kể cả khi vì lý do nào đó session lỡ được start.
		$session = $this->resolveStartedSession();
		if (!$session) {
			return;
		}

		// Đồng bộ user mới nhất từ guard vào session store trước khi save.
		if ($this->application->bound('auth')) {
			try {
				$this->application['auth']->user();
			}
			catch (\Exception $e) {
				// Tránh sập trang nếu Auth cấu hình sai lệch.
			}
		}

		// 1. Persist xuống DB (user_id đã được gán vào session data).
		$session->save();

		// 2. Gắn lại store vào request.
		$this->request->setLaravelSession($session);

		// 3. Re-emit cookie (shutdown đôi khi headers đã gửi — emitCookies tự guard).
		$this->emitCookies($this->buildSessionCookies($session));
	}

	public function shareErrorsToViews() {
		if ($this->application->bound('view') && $this->application->bound('session.store')) {
			$errors = $this->application['session.store']->get('errors', new \Illuminate\Support\ViewErrorBag());
			$this->application['view']->share('errors', $errors);
		}
	}

	/*
	 * Session cookie helpers
	 */

	/**
	 * Trả về session store nếu đã thực sự start, ngược lại null.
	 * Gộp toàn bộ guard: WP internal, chưa bound, chưa start.
	 */
	private function resolveStartedSession(): ?\Illuminate\Session\Store {
		if ($this->funcs->_isWPInternalRequest()) {
			return null;
		}
		if (!$this->application->bound('session.store')) {
			return null;
		}

		/** @var \Illuminate\Session\Store $session */
		$session = $this->application['session.store'];

		return $session->isStarted() ? $session : null;
	}

	/**
	 * Dựng cả Auth cookie và XSRF cookie từ session.
	 *
	 * @return string[] danh sách header value đã render sẵn.
	 */
	private function buildSessionCookies(\Illuminate\Session\Store $session): array {
		$sessionConfig = $this->application['session']->getSessionConfig();
		$configSession = $this->funcs->_config('session');

		$lifetime = $sessionConfig['lifetime'];
		$path     = $configSession['path'];
		$domain   = $configSession['domain'];
		$secure   = $configSession['secure'] ?? true;
		$sameSite = $sessionConfig['same_site'] ?? 'Lax';

		// Auth cookie (httpOnly = true).
		$authCookie = cookie(
			$session->getName(),
			$session->getId(),
			$lifetime, $path, $domain, $secure, true, false, $sameSite
		);

		// XSRF cookie (httpOnly = false để JS đọc được).
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
			$lifetime, $path, $domain, $secure, false, false, $sameSite
		);

		return [(string)$authCookie, (string)$xsrfCookie];
	}

	/**
	 * Ghi các cookie header ra client, chỉ khi headers chưa gửi.
	 *
	 * @param string[] $cookies
	 */
	private function emitCookies(array $cookies): void {
		if (headers_sent()) {
			return;
		}
		foreach ($cookies as $cookie) {
			@header('Set-Cookie: '.$cookie, false);
		}
	}

	/*
	 * Auth
	 */

	/**
	 * Override SessionGuard để đổi remember_web_* → wpsp_remember_web_*.
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
					$app['funcs']
				);

				$guard->setCookieJar($app['cookie']);
				$guard->setRequest($app['request']);

				return $guard;
			});
		});
	}

}