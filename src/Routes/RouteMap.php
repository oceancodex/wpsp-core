<?php

namespace WPSPCORE\Routes;

use Illuminate\Support\Facades\File;
use WPSPCORE\BaseInstances;

class RouteMap extends BaseInstances {

	public array $map = [];

	/*
	 *
	 */

	public function getMap(): array {
		return $this->map;
	}

	/*
	 *
	 */

	public function add($route): void {
		$type      = $route->type;
		$name      = $route->name;
		$path      = $route->path;
		$fullPath  = $route->fullPath;
		$namespace = $route->namespace;
		$version   = $route->version;

		if (!isset($this->map[$type])) {
			$this->map[$type] = [];
		}

		$this->map[$type][$name] = [
			'name'      => $name,
			'file'      => 'routes/' . $type . '.php',
			'line'      => (new \Exception())->getTrace()[1]['line'] ?? 0,
			'namespace' => $namespace,
			'version'   => $version,
			'path'      => $path,
			'full_path' => $fullPath,
		];
	}

	public function build(): void {
		$filePath             = $this->funcs->_getMainPath('/.wpsp-routes.json');
		$prepareMap           = [];
		$prepareMap['scope']  = $this->funcs->_getPluginDirName();
		$prepareMap['routes'] = $this->map;
		$prepareMap           = json_encode($prepareMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		File::put($filePath, $prepareMap);
	}

}