<?php

namespace WPSPCORE\Routes;

use WPSPCORE\Base\BaseInstances;

class RouteMap extends BaseInstances {

	public array $map     = [];
	public array $mapIdea = [];

	public function getMap() {
		return $this->map;
	}

	public function setMap(array $map): void {
		$this->map = $map;
	}

	public function getMapIdea() {
		return $this->mapIdea;
	}

	public function setMapIdea(array $mapIdea): void {
		$this->mapIdea = $mapIdea;
	}

}