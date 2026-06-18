<?php

namespace WPSPCORE\App\Http\Middleware;

use Closure;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;

class StartSessionIfAuthenticated {

	/**
	 * @var \Illuminate\Session\SessionManager
	 */
	protected $sessionManager;

	/** @var Encrypter */
	protected $encrypter;

	/*
	 *
	 */

	public function __construct(SessionManager $sessionManager, Encrypter $encrypter) {
		$this->sessionManager = $sessionManager;
		$this->encrypter      = $encrypter;
	}

	/*
	 *
	 */

	/**
	 * Start session and attach to request, then set request to auth factory.
	 * This middleware is safe to run for REST/API requests.
	 */
	public function handle(Request $request, Closure $next, $args = []) {
		try {
			$config = $args['funcs']->_config('session');

			/** @var \Illuminate\Session\Store $session */
			$session       = $this->sessionManager->driver();
			$sessionConfig = $this->sessionManager->getSessionConfig();

			$sessionCookieName = $session->getName();
			$clientSessionId   = $request->cookie($sessionCookieName);

			if ($clientSessionId && $session->getHandler()->read($clientSessionId)) {
				$session->setId($clientSessionId);
				if (!$session->isStarted()) {
					$session->start();
				}
			}
			else {
				if (!$session->isStarted()) {
					$userAgent = $request->userAgent();
					if ($userAgent && !preg_match('/WordPress\//', $userAgent)) {
						$session->start();
						$newSessionId = $session->getId();

						$session->save();
						$session->setId($newSessionId);

						$attributes = $session->all();
						foreach ($attributes as $key => $value) {
							$session->put($key, $value);
						}

						$session->save();

						/**
						 * Tạo cookie session và gửi về Client.
						 */
						$cookie = cookie(
							$session->getName(),
							$session->getId(),
							$sessionConfig['lifetime'],
							$config['path'],
							$config['domain'],
							true,
							true,
							false,
							$sessionConfig['same_site']
						);
						header('Set-Cookie: ' . $cookie, false);
					}
				}
			}

			$request->setLaravelSession($session);

			/**
			 * Tạo cookie XSRF-TOKEN và gửi về Client.
			 */
			$xsrfName   = $session->getName() . '-XSRF-TOKEN';
			$xsrfPrefix = CookieValuePrefix::create($xsrfName, $this->encrypter->getKey());
			$xsrfToken  = $this->encrypter->encrypt($xsrfPrefix . $session->token(), EncryptCookies::serialized('XSRF-TOKEN'));

			$xsrfCookie = cookie(
				$xsrfName,
				$xsrfToken,
				$sessionConfig['lifetime'],
				$config['path'],
				$config['domain'],
				$config['secure'],
				false,
				false,
				$sessionConfig['same_site']
			);
			header('Set-Cookie: ' . $xsrfCookie, false);

			return $next($request);
		}
		catch (\Throwable $e) {
			return $next($request);
		}
	}

}