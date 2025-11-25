<?php

namespace WPSPCORE\Traits;

trait RouteTrait {

	private array $routes         = [];
	public bool   $isForRouterMap = false;

	public function _prefix($prefix) {
		return $this;
	}

	public function _name($name) {
		return $this;
	}

	/**
	 * @return static
	 */
	public function _middleware($middleware) {
		return $this;
	}

	public function _group($callback) {
		return $this;
	}

}