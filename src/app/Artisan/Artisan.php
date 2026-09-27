<?php

namespace WPSPCORE\App\Artisan;

use Illuminate\Foundation\Console\Kernel;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Foundation\Console\Kernel
 * @mixin \Illuminate\Support\Facades\Artisan
 */
abstract class Artisan extends BaseInstances {

	private ?Kernel $artisan;

	/*
	 *
	 */

	public function getArtisan(): ?Kernel {
		return $this->artisan;
	}

	public function setArtisan() {
		$this->artisan = $this->funcs->_getApplication(\Illuminate\Foundation\Console\Kernel::class);
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

		return $instance->getArtisan()?->$method(...$arguments);
	}

}