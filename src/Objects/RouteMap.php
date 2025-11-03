<?php

namespace WPSPCORE\Objects;

class RouteMap extends \WPSPCORE\Base\BaseInstances {

	public $map     = [];
	public $mapIdea = [];

	public function getMap() {
		return $this->map;
	}

	public function getMapIdea() {
		return $this->mapIdea;
	}

	public function setMap($map) {
		$this->map = $map;
	}

	public function setMapIdea($mapIdea) {
		$this->mapIdea = $mapIdea;
	}

}