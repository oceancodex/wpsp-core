<?php

namespace WPSPCORE\App\Schema;

use Illuminate\Database\Schema\Builder as SchemaCore;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\Schema
 */
abstract class Schema extends BaseInstances {

	private ?SchemaCore $facade;

	/*
	 *
	 */

	public function getFacade(): ?SchemaCore {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication('db.schema');
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