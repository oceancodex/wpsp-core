<?php

namespace WPSPCORE\App\File;

use Illuminate\Filesystem\Filesystem as IlluminateFile;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\File
 */
abstract class File extends BaseInstances {

	private ?IlluminateFile $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateFile {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('files');
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