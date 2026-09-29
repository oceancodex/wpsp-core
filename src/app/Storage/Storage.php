<?php

namespace WPSPCORE\App\Storage;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Storage
 */
abstract class Storage extends BaseInstances {

	private ?FilesystemManager $facade;

	/*
	 *
	 */

	public function getFacade(): ?FilesystemManager {
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