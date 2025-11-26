<?php

namespace WPSPCORE\Auth;

use WPSPCORE\Base\BaseInstances;

abstract class Auth extends BaseInstances {

	/** @var \Illuminate\Support\Facades\Auth */
	public $auth;

	/*
	 *
	 */

	public function setAuth(): void {
		$this->auth = static::$funcs->getApplication('auth');
	}

	public function getAuth() {
		return $this->auth;
	}

	/*
	 *
	 */

	public function attempt($credentials, $remember = false) {
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

	public function logout(): void {
		$this->auth->logout();
		$this->saveSessionsAndCookies();
	}

	/*
	 *
	 */

	protected function saveSessionsAndCookies(): void {
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

	protected function cleanupOldSessionsForUser($userId): void {
		$db = $this->funcs->getApplication('db'); // hoặc DB::connection()

		// Xóa tất cả session cùng user_id trước đó.
		$db->table('sessions')
			->where('user_id', $userId)
			->delete();
	}

	/*
	 *
	 */

	public function __call($name, $arguments) {
		if (method_exists(static::instance(), $name)) {
			return static::instance()->$name(...$arguments);
		}
		else {
			return static::instance()->getAuth()->$name(...$arguments);
		}
	}

	public static function __callStatic($name, $arguments) {
		if (method_exists(static::instance(), $name)) {
			return static::instance()->$name(...$arguments);
		}
		else {
			return static::instance()->getAuth()->$name(...$arguments);
		}
	}

}