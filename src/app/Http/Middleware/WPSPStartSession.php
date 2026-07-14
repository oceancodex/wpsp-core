<?php

namespace WPSPCORE\App\Http\Middleware;

use Closure;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;

class WPSPStartSession {

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

		// Không start session cho asset tĩnh / favicon — tránh tạo row session rác.
		$uri = $request->getPathInfo();
		if (preg_match('#\.(ico|png|jpe?g|gif|svg|webp|css|js|map|txt|xml|woff2?|ttf|eot)$#i', $uri)) {
			return $next($request);
		}

		/** @var \Illuminate\Session\Store $session */
		$session = $args['funcs']->_getApplication('session.store');

		$sessionCookieName = $session->getName();
		$clientSessionId   = $request->cookie($sessionCookieName);

		if ($clientSessionId) {
			$session->setId($clientSessionId);

			if (!$session->isStarted()) {
				$session->start();
			}

		}
		else {
			// Không có cookie và là client thật → tạo session mới.
			if (!$session->isStarted()) {
				$session->start();
			}
		}

		return $next($request);
	}

}