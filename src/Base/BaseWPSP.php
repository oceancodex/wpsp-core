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
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;

abstract class BaseWPSP extends BaseInstances {

	public ?Application $application = null;

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
			->withMiddleware(function(Middleware $middleware): void {
				$middleware->append(\Illuminate\Session\Middleware\StartSession::class);
			})
			->withExceptions(function(Exceptions $exceptions): void {
				//
			})->create();

		$this->bootstrap();
		$this->bindings();
		$this->handleRequest();

		$this->application->boot();

//		$this->authSaveCookie();
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
		// Files.
		$this->application->singleton('files', function() {
			return new Filesystem();
		});
		// Request.
		$this->application->singleton('request', function() {
			return Request::capture();
		});
		// Session.
		$this->application->register(\Illuminate\Session\SessionServiceProvider::class);
	}

	protected function handleRequest(): void {
//		add_action('parse_request', function(\WP $wp) {
			$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
			$isWpInternal = (stripos($ua, 'WordPress') !== false && (!isset($_GET['doing_wp_cron']) || php_sapi_name() !== 'cli-server'));

			// Trường hợp WordPress tự gọi wp-cron → KHÔNG chạy Kernel, KHÔNG tạo session
			if ($isWpInternal) {
				$this->application->instance('request', \Illuminate\Http\Request::capture());
				return;
			}

			// Trường hợp người dùng TRUY CẬP TRỰC TIẾP wp-cron từ trình duyệt
			$request = \Illuminate\Http\Request::capture();
			$this->application->instance('request', $request);

			$kernel   = $this->application->make(\Illuminate\Contracts\Http\Kernel::class);
			$response = $kernel->handle($request);
			$response->send();
			$kernel->terminate($request, $response);
//		}, 0);
	}

	/*
	 *
	 */

	protected function authSaveCookie(): void {
		register_shutdown_function(function() {

			// 1. Không xử lý session cho các request nội bộ của WordPress
			$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
			if (stripos($ua, 'WordPress') !== false && !isset($_GET['doing_wp_cron'])) {
				return; // Không save session rác
			}

			// 2. Đảm bảo session tồn tại
			if (!$this->application || !$this->application->bound('session')) {
				return;
			}

			try {
				/** @var \Illuminate\Session\Store $session */
				$session = $this->application['session'];

				// 3. Nếu session không start và rỗng thì không cần save
				if (!$session->isStarted() && empty($session->all())) {
					return;
				}

				// 4. Không ghi lại session nếu payload không đổi
				$original = $session->getHandler()->read($session->getId());
				$payload  = $session->all();

				if ($original) {
					$decoded = @unserialize($original);

					if (is_array($decoded) && isset($decoded['data'])) {
						if (serialize($payload) === serialize($decoded['data'])) {
							// payload không đổi → KHÔNG cần save
							goto set_cookie_only;
						}
					}
				}

				// 5. Save session nếu có thay đổi
				$session->save();

				set_cookie_only:

				// 6. Headers đã gửi → không set cookie nữa
				if (headers_sent()) {
					return;
				}

				// 7. Lấy config chuẩn từ Laravel
				$config     = $this->application['config']->get('session', []);
				$cookieName = $config['cookie'] ?? $this->funcs->_getAppShortName() . '-session';
				$lifetime   = (int)($config['lifetime'] ?? 120) * 60;
				$path       = $config['path'] ?? '/';
				$domain     = $config['domain'] ?: null;
				$secure     = $config['secure'] ?? (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
				$httpOnly   = $config['http_only'] ?? true;
				$sameSite   = $config['same_site'] ?? 'Lax';

				$sessionId = $session->getId();
				if (!$sessionId) return;

				// 8. Set cookie chuẩn theo PHP 7.3+
				@setcookie($cookieName, $sessionId, [
					'expires'  => time() + $lifetime,
					'path'     => $path,
					'domain'   => $domain,
					'secure'   => (bool)$secure,
					'httponly' => (bool)$httpOnly,
					'samesite' => $sameSite,
				]);

			} catch (\Throwable $e) {
				// Im lặng: không được để crash trong shutdown
			}
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