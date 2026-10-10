<?php

namespace WPSPCORE\App\Support\Facades;

use Illuminate\Database\DatabaseManager as IlluminateDB;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\DB
 */
abstract class DB extends BaseInstances {

	private ?IlluminateDB $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateDB {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('db');
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