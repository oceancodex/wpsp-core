<?php

namespace WPSPCORE\app\Database;

use Illuminate\Database\DatabaseManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Database\DatabaseManager
 * @mixin \Illuminate\Support\Facades\DB
 */
abstract class DB extends BaseInstances {

	private DatabaseManager $db;

	/*
	 *
	 */

	public function getDB(): DatabaseManager {
		return $this->db;
	}

	public function setDB() {
		$this->db = $this->funcs->getApplication('db');
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

		return $instance->getDB()->$method(...$arguments);
	}

}