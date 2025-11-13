<?php

namespace WPSPCORE\Http\Middleware;

use Closure;
use Illuminate\Session\Middleware\StartSession;

class StartSessionIfAuthenticated extends StartSession {

	public function handle($request, Closure $next) {
		// Nếu đã có session cookie → session restore bình thường
		$cookieName = config('session.cookie');
		if ($request->cookies->has($cookieName)) {
			return parent::handle($request, $next);
		}

		// Nếu gọi Auth::user() mà chưa login → KHÔNG TẠO SESSION
		if (!auth()->check()) {
			return $next($request);
		}

		// Nếu user đã login → tạo session bình thường
		return parent::handle($request, $next);
	}

}
