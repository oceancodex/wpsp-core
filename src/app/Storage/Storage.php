<?php

namespace WPSPCORE\App\Storage;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Filesystem\Filesystem
 * @mixin \Illuminate\Support\Facades\Storage
 */
abstract class Storage extends BaseInstances {

	private FilesystemManager $storage;

	/*
	 *
	 */

	public function getStorage(): FilesystemManager {
		return $this->storage;
	}

	public function setStorage() {
		$this->storage = $this->funcs->getApplication('filesystem');
//		$this->storage = $this->funcs->getApplication('storage');
//		$this->storage = $this->funcs->getApplication(FilesystemManager::class);
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		$instance = static::instance();

		$underlineMethod = '_' . $method;
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		return $instance->getStorage()->$method(...$arguments);
	}

}