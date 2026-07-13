<?php

namespace WPSPCORE\App\Http\Middleware;

use Closure;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;

class WPSPStartSession {

	/**
	 * @var \Illuminate\Session\SessionManager
	 */
	protected $sessionManager;

	/** @var Encrypter */
	protected $encrypter;

	/*
	 *
	 */

	public function __construct(SessionManager $sessionManager, Encrypter $encrypter) {
		$this->sessionManager = $sessionManager;
		$this->encrypter      = $encrypter;
	}

	/*
	 *
	 */

	/**
	 * Start session and attach to request, then set request to auth factory.
	 * This middleware is safe to run for REST/API requests.
	 */
	public function handle(Request $request, Closure $next, $args = []) {
		// Bỏ qua hoàn toàn với CRON / CLI / WP loopback — không tạo session,
		// không set cookie, không phát XSRF. Đây là chỗ chặn UA "WordPress/..."
		// đang tạo row session rác trong DB.
		if ($args['funcs']->isWPInternalRequest($request)) {
			return $next($request);
		}

		try {
			/** @var \Illuminate\Session\Store $session */
			$session = $this->sessionManager->driver();

			$sessionCookieName = $session->getName();
			$clientSessionId   = $request->cookie($sessionCookieName);

			if ($clientSessionId) {
				// Đặt id từ cookie rồi start — start() đọc DB đúng 1 lần.
				$session->setId($clientSessionId);

				if (!$session->isStarted()) {
					$session->start();
				}

				// Session trống nghĩa là row không tồn tại trong DB
				// (bị xóa thủ công, hết hạn, hoặc id giả mạo).
				// Cấp id mới thay vì tái dùng id "mồ côi" (chống session fixation).
				if (empty($session->all())) {
					$session->migrate(true);
				}
			}
			else {
				// Không có cookie và là client thật → tạo session mới.
				if (!$session->isStarted()) {
					$session->start();
				}
			}

//			$request->setLaravelSession($session);

			return $next($request);
		}
		catch (\Throwable $e) {
			return $next($request);
		}
	}

}