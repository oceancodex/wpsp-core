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
		return $next($request);
	}

}
