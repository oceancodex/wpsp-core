<?php

namespace WPSPCORE\Base;

use WPSPCORE\Traits\BaseInstancesTrait;

abstract class BaseInstances {

	use BaseInstancesTrait;

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		$this->beforeBaseInstanceConstruct(
			$mainPath,
			$rootNamespace,
			$prefixEnv,
			$extraParams
		);
	}

}