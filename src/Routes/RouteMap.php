<?php

namespace WPSPCORE\Routes;

use Illuminate\Support\Facades\File;
use WPSPCORE\Base\BaseInstances;

class RouteMap extends BaseInstances {

	public array $map = [];

	/** @var static */
	public static $instance;

	/*
	 *
	 */

	public static function instance() {
		if (!static::$instance) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/*
	 *
	 */

	public function getMap(): array {
		return $this->map;
	}

	public function build() {
		$filePath             = static::$funcs->_getMainPath('/.wpsp-routes.json');
		$prepareMap           = [];
		$prepareMap['scope']  = static::$funcs->_getPluginDirName();
		$prepareMap['routes'] = $this->map;
		$prepareMap           = json_encode($prepareMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		File::put($filePath, $prepareMap);
	}

	public function add($route) {
		$type     = $route->type;
		$name     = $route->name;
		$path     = $route->path;
		$fullPath = $route->fullPath;

		if (!isset($this->map[$type])) {
			$this->map[$type] = [];
		}

		$this->map[$type][] = [
			'name'      => $name,
			'file'      => 'routes/' . $type . '.php',
			'line'      => (new \Exception())->getTrace()[1]['line'] ?? 0,
			'namespace' => 'wpsp',
			'version'   => 'v1',
			'path'      => $path,
			'full_path' => $fullPath,
		];
	}

}