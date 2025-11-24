<?php

namespace WPSPCORE\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class StartSessionIfAuthenticated {

	/**
	 * @var \Illuminate\Session\SessionManager
	 */
	protected $sessionManager;

	/**
	 * @var \Illuminate\Contracts\Auth\Factory
	 */
	protected $authFactory;

	public function __construct(SessionManager $sessionManager, AuthFactory $authFactory) {
		$this->sessionManager = $sessionManager;
		$this->authFactory    = $authFactory;
	}

	/**
	 * Start session and attach to request, then set request to auth factory.
	 * This middleware is safe to run for REST/API requests.
	 */
	public function handle(Request $request, Closure $next) {
		/** @var \Illuminate\Session\Store $session */
		$session       = $this->sessionManager->driver();
		$sessionConfig = $this->sessionManager->getSessionConfig();

		$sessionCookieName = $session->getName();
		$clientSessionId   = $request->cookie($sessionCookieName);

		if ($clientSessionId) {
			$session->setId($clientSessionId);
		}
		else {
			$cookie = cookie(
				$session->getName(),
				$session->getId(),
				$sessionConfig['lifetime'],
				'/',
				null,
				true,
				true,
				false,
				$sessionConfig['same_site']
			);

			header('Set-Cookie: ' . $cookie, false);
		}

		if (!$session->isStarted()) {
			$session->start();
			if (!$clientSessionId) {
				$userAgent = $request->userAgent();
				if (!preg_match('/WordPress\//', $userAgent)) {
					$session->save();
				}
			}
		}

		$request->setLaravelSession($session);

		return $next($request);
	}

}
