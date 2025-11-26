<?php

namespace WPSPCORE\Auth;

use Illuminate\Auth\AuthManager;
use WPSPCORE\Base\BaseInstances;

abstract class Auth extends BaseInstances {

	public AuthManager $auth;

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

	public function attempt($credentials, $remember = false): bool {
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
		$session       = static::$funcs->getApplication('session');
		$clientSession = $_COOKIE[static::$funcs->_config('session.cookie')] ?? null;
		if ($clientSession) {
			$session->setId($clientSession);
			$session->save();
		}

		// Save cookies.
		$queued = static::$funcs->getApplication('cookie')->getQueuedCookies();
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
		$db = static::$funcs->getApplication('db'); // hoặc DB::connection()

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
		if (method_exists(static::instance(), $method)) {
			return static::instance()->$method(...$arguments);
		}
		else {
			return static::instance()->getAuth()->$method(...$arguments);
		}
	}

}