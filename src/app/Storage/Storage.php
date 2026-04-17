<?php

namespace WPSPCORE\App\Storage;

use Illuminate\Process\Factory;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Filesystem\FilesystemManager
 * @mixin \Illuminate\Support\Facades\Storage
 */
abstract class Storage extends BaseInstances {

	private Factory $process;

	/*
	 *
	 */

	public function getProcess(): Factory {
		return $this->process;
	}

	public function setProcess() {
		$this->process = $this->funcs->getApplication('process');
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

		return $instance->getProcess()->$method(...$arguments);
	}

}