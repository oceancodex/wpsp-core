<?php

namespace WPSPCORE\App\MaintenanceMode;

use Illuminate\Foundation\MaintenanceModeManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Support\Facades\MaintenanceMode
 */
abstract class MaintenanceMode extends BaseInstances {

	private ?MaintenanceModeManager $facade;

	/*
	 *
	 */

	public function getFacade(): ?MaintenanceModeManager {
		return $this->facade;
	}

	public function setFacade() {
		$this->facade = $this->funcs->_getApplication(MaintenanceModeManager::class);
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