<?php

namespace WPSPCORE;

use WPSPCORE\Base\BaseInstances;

class Container extends BaseInstances {

	/** @var null|\Illuminate\Container\Container */
	public $container = null;

	public function afterConstruct() {
		$this->container = \Illuminate\Container\Container::getInstance();
	}

	/**
	 * @return null|\Illuminate\Container\Container
	 */
	public function getContainer() {
		return $this->container;
	}

}