<?php

namespace WPSPCORE\App\Cloud;

use Illuminate\Foundation\Cloud\CloudManager;
use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\Foundation\Cloud\CloudManager
 * @mixin \Illuminate\Support\Facades\Cloud
 */
abstract class Cloud extends BaseInstances {

	private ?CloudManager $cloud;

	/*
	 *
	 */

	public function getCloud(): ?CloudManager {
		return $this->cloud;
	}

	public function setCloud() {
		$this->cloud = $this->funcs->_getApplication(CloudManager::class);
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

		return $instance->getCloud()?->$method(...$arguments);
	}

}