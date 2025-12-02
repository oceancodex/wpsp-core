<?php

namespace WPSPCORE\App\Auth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Timebox;
use Symfony\Component\HttpFoundation\Request;

class SessionGuard extends \Illuminate\Auth\SessionGuard {

	/** @var \WPSPCORE\App\Funcs|null */
	public $funcs = null;

	/**
	 * Create a new authentication guard.
	 */
	public function __construct(
		$name,
		UserProvider $provider,
		Session $session,
		?Request $request = null,
		?Timebox $timebox = null,
		bool $rehashOnLogin = true,
		int $timeboxDuration = 200000,
		$funcs = null
	) {
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
	public function getRecallerName(): string {
		return $this->funcs->_getAppShortName() . '_remember_' . $this->name . '_' . sha1(static::class);
	}

}