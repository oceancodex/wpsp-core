<?php

namespace WPSPCORE\App\App;

use Illuminate\Foundation\Application;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Foundation\Application
 * @mixin \Illuminate\Support\Facades\App
 */
abstract class App extends BaseInstances {

	private ?Application $app;

	/*
	 *
	 */

	public function getApp(): ?Application {
		return $this->app;
	}

	public function setApp() {
		$this->app = $this->funcs->_getApplication();
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		$instance = static::wpspInstance();

		$underlineMethod = '_' . $method;
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		return $instance->getApp()?->$method(...$arguments);
	}

}