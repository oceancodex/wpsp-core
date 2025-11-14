<?php

namespace WPSPCORE\Routes;

use Illuminate\Support\Facades\File;
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

	public function remap() {
		$filePath = $this->funcs->_getMainPath('/.wpsp-routes.json');
		$prepareMap           = [];
		$prepareMap['scope']  = $this->funcs->_getPluginDirName();
		$prepareMap['routes'] = $this->mapIdea;
		$prepareMap           = json_encode($prepareMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		File::put($filePath, $prepareMap);
	}

}