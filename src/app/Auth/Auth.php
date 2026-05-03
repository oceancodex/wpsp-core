<?php

namespace WPSPCORE\App\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Auth
 */
abstract class Auth extends BaseInstances {

	public AuthManager $auth;

	/*
	 *
	 */

	public function setAuth() {
		$this->auth = $this->funcs->getApplication('auth');
	}

	public function getAuth(): AuthManager {
		return $this->auth;
	}

	/*
	 *
	 */

	public function _login(AuthenticatableContract $user, $remember = false) {
		try {
			$this->auth->login($user, $remember);
			$this->saveSessionsAndCookies();
		}
		catch (\Exception $e) {

		}
	}

	public function _attempt($credentials, $remember = false) {
		try {
			$attempt = $this->auth->attempt($credentials, $remember);

			if ($attempt) {
				$user = $this->auth->user();
				if ($user) {
					$this->cleanupOldSessionsForUser($user->getAuthIdentifier());
				}
			}

			$this->saveSessionsAndCookies();
			return $attempt;
		}
		catch (\Exception $e) {
			return false;
		}
	}

	public function _logout() {
		try {
			$this->auth->logout();
			$this->saveSessionsAndCookies();
		}
		catch (\Exception $e) {}
	}

	/*
	 *
	 */

	protected function saveSessionsAndCookies() {
		// Save session.
		$session       = $this->funcs->getApplication('session');
		$clientSession = $_COOKIE[$this->funcs->_config('session.cookie')] ?? null;
		if ($clientSession) {
			$session->setId($clientSession);
			$session->save();
		}

		// Save cookies.
		$queued = $this->funcs->getApplication('cookie')->getQueuedCookies();
		foreach ($queued as $cookie) {
			setcookie(
				$cookie->getName(),
				$cookie->getValue(),
				[
					'expires'  => $cookie->getExpiresTime(),
					'path'     => $cookie->getPath(),
					'domain'   => $cookie->getDomain(),
					'secure'   => $cookie->isSecure(),
					'httponly' => $cookie->isHttpOnly(),
					'samesite' => $cookie->getSameSite(),
				]
			);
		}
	}

	protected function cleanupOldSessionsForUser($userId) {
		$db = $this->funcs->getApplication('db'); // hoặc DB::connection()

		// Xóa tất cả session cùng user_id trước đó.
		$db->table('sessions')
			->where('user_id', $userId)
			->delete();
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		$instance = static::instance();

		$underlineMethod = '_' . $method;
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		return $instance->getAuth()->$method(...$arguments);
	}

}