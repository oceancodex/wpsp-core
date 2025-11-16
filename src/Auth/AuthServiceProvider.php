<?php
namespace WPSPCORE\Auth;

use Illuminate\Cookie\CookieJar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Timebox;

class AuthServiceProvider extends \Illuminate\Auth\AuthServiceProvider {

	/**
	 * Register services.
	 */
//	public function register(): void {
//		parent::register();
//	}

	/**
	 * Bootstrap services.
	 */
	public function boot(): void {
		Auth::extend('session', function ($app, $name, array $config) {

			// 1. User provider
			$provider = Auth::createUserProvider($config['provider']);

			// 2. Tạo guard
			$guard = new SessionGuard(
				$name,
				$provider,
				$app['session.store'],
				$app['request'],
				$app->make(Timebox::class),
				true,
				200000,
				$app['funcs']
			);

			// -----------------------------------------------
			// 🔥 PHẦN BẠN BỊ THIẾU (gây ra lỗi Cookie Jar)
			// -----------------------------------------------

			// 3. CookieJar (bắt buộc)
			// Nếu WordPress bootstrap chưa có cookie thì tạo mới
			if (!$app->bound('cookie')) {
				$app->instance('cookie', new CookieJar());
			}

			$guard->setCookieJar($app['cookie']);

			// 5. Request (để SessionGuard xử lý remember cookie)
			$guard->setRequest($app['request']);

			return $guard;
		});
	}

}