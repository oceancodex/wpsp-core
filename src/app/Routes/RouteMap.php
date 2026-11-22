<?php

namespace WPSPCORE\App\Routes;

use Illuminate\Support\Facades\File;
use WPSPCORE\BaseInstances;

class RouteMap extends BaseInstances {

	public $map = [];

	/*
	 *
	 */

	public function getMap() {
		return $this->map;
	}

	public function getRoute($routeClass, $routeName) {
		return $this->map[$routeClass][$routeName] ?? null;
	}

	/*
	 *
	 */

	public function add($route) {
		$type          = $route->type;
		$name          = $route->name;
		$path          = $route->path;
		$pathRegex     = $route->pathRegex;
		$fullPathRegex = $route->fullPathRegex;
		$fullPath      = $route->fullPath;
		$namespace     = $route->namespace;
		$version       = $route->version;

		if (!isset($this->map[$type])) {
			$this->map[$type] = [];
		}

		preg_match_all('/\(\?P?<([a-zA-Z_][a-zA-Z0-9_]*)>/', $fullPathRegex, $matches);
		$routeParameters = $matches[1] ?? [];

//		if ($type == 'AdminPages') {
//			$routeParameters[] = 'force_init';
//			$routeParameters[] = 'force_init_slug';
//		}
//		elseif ($type == 'Actions' || $type == 'Filters') {
//			$routeParameters[] = 'priority';
//			$routeParameters[] = 'accepted_args';
//		}
//		elseif ($type == 'Ajaxs') {
//			$routeParameters[] = 'nopriv';
//		}
//		elseif ($type == 'Schedules') {
//			$routeParameters[] = 'interval';
//		}

		$this->map[$type][$name] = [
			'name'             => $name,
			'file'             => 'routes/' . $type . '.php',
			'line'             => (new \Exception())->getTrace()[1]['line'] ?? 0,
			'namespace'        => $namespace,
			'version'          => $version,
			'path'             => $path,
			'path_regex'       => $pathRegex,
			'full_path'        => $fullPath,
			'full_path_regex'  => $fullPathRegex,
			'route_parameters' => $routeParameters,
			'route_data'       => $route,
		];
	}

	public function build() {
		$filePath             = $this->funcs->_getMainPath('/.wpsp-routes.json');
		$prepareMap           = [];
		$prepareMap['scope']  = $this->funcs->_getPluginDirName();
		$prepareMap['routes'] = $this->map;
		$prepareMap           = json_encode($prepareMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		File::put($filePath, $prepareMap);
	}

}