<?php

namespace WPSPCORE\App\Widen\Support\Facades;

if (class_exists('Illuminate\Support\Facades\Facade')) {
	abstract class Facade extends \Illuminate\Support\Facades\Facade {}
}
else {
	/**
	 * Facade - mô phỏng Illuminate\Support\Facades\Facade bằng PHP thuần.
	 *
	 * Request::input('id') => static::$app->make('request')->input('id')
	 *
	 * Khác Laravel ở 2 điểm (có chủ đích):
	 * - Không cache instance theo accessor: luôn lấy từ container, nên rebind
	 *   ($app->instance('request', ...)) hay đổi app (nhiều plugin) đều có hiệu lực ngay.
	 *   Object đã resolve vẫn được container giữ, nên chi phí chỉ là 1 lần tra mảng.
	 * - Có __call: lỡ type-hint class facade thì $facade->input() vẫn chạy.
	 */
	abstract class Facade {

		/** @var \WPSPCORE\App\Widen\Container|\ArrayAccess|null */
		protected static $app;

		/** accessor => instance thay thế (swap()). */
		protected static $resolvedInstance = [];

		/**
		 * Tên binding trong container (vd: 'request') hoặc chính object.
		 *
		 * @return string|object
		 */
		protected static function getFacadeAccessor() {
			throw new \RuntimeException('Facade does not implement getFacadeAccessor method.');
		}

		public static function getFacadeRoot() {
			return static::resolveFacadeInstance(static::getFacadeAccessor());
		}

		protected static function resolveFacadeInstance($name) {
			if (is_object($name)) {
				return $name;
			}

			if (isset(static::$resolvedInstance[$name])) {
				return static::$resolvedInstance[$name];
			}

			return static::$app ? static::$app[$name] : null;
		}

		/**
		 * Thay instance phía sau facade (test, mock): Request::swap($fakeRequest)
		 */
		public static function swap($instance) {
			static::$resolvedInstance[static::getFacadeAccessor()] = $instance;

			if (static::$app) {
				static::$app->instance(static::getFacadeAccessor(), $instance);
			}
		}

		public static function clearResolvedInstance($name) {
			unset(static::$resolvedInstance[$name]);
		}

		public static function clearResolvedInstances() {
			static::$resolvedInstance = [];
		}

		public static function getFacadeApplication() {
			return static::$app;
		}

		/**
		 * Đổi app cũng xoá các instance đã swap() để không dùng nhầm của app khác.
		 */
		public static function setFacadeApplication($app) {
			if (static::$app !== $app) {
				static::$resolvedInstance = [];
			}
			static::$app = $app;
		}

		public static function __callStatic($method, $args) {
			$instance = static::getFacadeRoot();

			if (!$instance) {
				throw new \RuntimeException('A facade root has not been set.');
			}

			return $instance->$method(...$args);
		}

		public function __call($method, $args) {
			return static::__callStatic($method, $args);
		}

	}
}