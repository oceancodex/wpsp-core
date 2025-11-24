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

	public function __construct(SessionManager $sessionManager) {
		$this->sessionManager = $sessionManager;
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
			}
		}

		$request->setLaravelSession($session);

		return $next($request);
	}

}
