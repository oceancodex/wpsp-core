<?php

namespace WPSPCORE\App\Image;

use Illuminate\Image\ImageManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Image\ImageManager
 * @mixin \Illuminate\Support\Facades\Image
 */
abstract class Image extends BaseInstances {

	private ?ImageManager $facade;

	/*
	 *
	 */

	public function getFacade(): ?ImageManager {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('image');
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