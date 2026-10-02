<?php

namespace WPSPCORE\App\Storage;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager as IlluminateStorage;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Storage
 */
abstract class Storage extends BaseInstances {

	private ?IlluminateStorage $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateStorage {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('filesystem');
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