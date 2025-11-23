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
		$application   = $this->sessionManager->getContainer();

		$session       = $application->make('session');
		$funcs         = $application->make('funcs');

		$sessionStore  = $this->sessionManager->driver();

		$sessionCookieName = $sessionStore->getName();
		$clientSessionId   = $request->cookie($sessionCookieName);

		if ($clientSessionId) {
			$session->setId($clientSessionId);
		}
		else {
			$cookie = cookie(
				$sessionStore->getName(),
				$sessionStore->getId(),
				$funcs->_config('session.lifetime'),
				'/',
				null,
				true,
				true,
				false,
				$funcs->_config('session.same_site')
			);

			header('Set-Cookie: ' . $cookie, false);
		}

		// Start nếu chưa start
		if (!$session->isStarted()) {
			$session->start();
		}

		$request->setLaravelSession($sessionStore);

		return $next($request);
	}

}
