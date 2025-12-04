<?php

namespace WPSPCORE\App\Auth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Timebox;
use Symfony\Component\HttpFoundation\Request;

class SessionGuard extends \Illuminate\Auth\SessionGuard {

	/** @var \WPSPCORE\Funcs|null */
	public $funcs = null;

	/**
	 * Create a new authentication guard.
	 */
	public function __construct($name, $provider, $session, $request = null, $timebox = null, $rehashOnLogin = true, $timeboxDuration = 200000, $funcs = null) {
		parent::__construct(
			$name,
			$provider,
			$session,
			$request,
			$timebox,
			$rehashOnLogin,
			$timeboxDuration
		);

		$this->funcs = $funcs;
	}

	/**
	 * Get the name of the cookie used to store the "recaller".
	 *
	 * @return string
	 */
	public function getRecallerName() {
		return $this->funcs->_getAppShortName() . '_remember_' . $this->name . '_' . sha1(static::class);
	}

}