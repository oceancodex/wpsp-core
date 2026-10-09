<?php

namespace WPSPCORE\App\Support\Facades;

use Illuminate\Foundation\MaintenanceModeManager as IlluminateMaintenanceMode;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\MaintenanceMode
 */
abstract class MaintenanceMode extends BaseInstances {

	private ?IlluminateMaintenanceMode $facade;

	/*
	 *
	 */

	public function getFacade(): ?IlluminateMaintenanceMode {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(IlluminateMaintenanceMode::class);
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