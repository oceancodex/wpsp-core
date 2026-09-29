<?php

namespace WPSPCORE\App\Database;

use Illuminate\Database\DatabaseManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\DB
 */
abstract class DB extends BaseInstances {

	private ?DatabaseManager $facade;

	/*
	 *
	 */

	public function getFacade(): ?DatabaseManager {
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