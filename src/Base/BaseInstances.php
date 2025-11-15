<?php

namespace WPSPCORE\Base;

use WPSPCORE\Traits\BaseInstancesTrait;

abstract class BaseInstances {

	use BaseInstancesTrait;

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		$this->baseInstanceConstruct($mainPath, $rootNamespace, $prefixEnv, $extraParams);
	}

	public function __set($name, $value) {
		$this->{$name} = $value;
	}

}