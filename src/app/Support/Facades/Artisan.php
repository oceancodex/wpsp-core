<?php

namespace WPSPCORE\App\Support\Facades;

use Illuminate\Contracts\Console\Kernel as IlluminateArtisan;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Artisan
 */
abstract class Artisan extends BaseInstances {

	private ?IlluminateArtisan $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateArtisan {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(IlluminateArtisan::class);
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

		return $instance->getFacade()?->$method(...$arguments);
	}

}