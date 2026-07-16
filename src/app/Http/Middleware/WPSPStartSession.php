<?php

namespace WPSPCORE\App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;

class WPSPStartSession {

	/**
	 * Start session and attach to request, then set request to auth factory.
	 * This middleware is safe to run for REST/API requests.
	 */
	public function handle(Request $request, Closure $next, $args = []) {
		// Bỏ qua hoàn toàn với CRON / CLI / WP loopback
		if ($args['funcs']->_isWPInternalRequest($request)) {
			return $next($request);
		}

		// Không start session cho asset tĩnh
		$uri = $request->getPathInfo();
		if (preg_match('#\.(ico|png|jpe?g|gif|svg|webp|css|js|map|txt|xml|woff2?|ttf|eot)$#i', $uri)) {
			return $next($request);
		}

		try {
			/** @var \Illuminate\Session\Store $session */
			$session = $args['funcs']->_getApplication('session.store');
			$sessionCookieName = $session->getName();

			// Lấy Cookie đã mã hóa từ Client gửi lên
			$rawCookie = $request->cookie($sessionCookieName);
			$clientSessionId = null;

			if ($rawCookie) {
				/** @var Encrypter $encrypter */
				$encrypter = $args['funcs']->_getApplication(Encrypter::class);

				try {
					// 1. Giải mã Cookie (Laravel mặc định serialize = false đối với Cookie thông thường)
					$decrypted = $encrypter->decrypt($rawCookie, false);

					// 2. Xác thực tiền tố bảo mật (CookieValuePrefix) để tránh giả mạo chéo tên Cookie
					$clientSessionId = CookieValuePrefix::validate(
						$sessionCookieName,
						$decrypted,
						$encrypter->getAllKeys()
					);
				} catch (DecryptException $e) {
					// Nếu giải mã thất bại (cookie bị can thiệp hoặc đổi APP_KEY), coi như session không hợp lệ
					$clientSessionId = null;
				}
			}

			if ($clientSessionId) {
				$session->setId($clientSessionId);

				if (!$session->isStarted()) {
					$session->start();
				}
			}
			else {
				// Không có cookie hợp lệ -> khởi tạo session mới hoàn toàn
				if (!$session->isStarted()) {
					$session->start();
				}
			}
		}
		catch (\Throwable $e) {
			return $next($request);
		}

		return $next($request);
	}
}