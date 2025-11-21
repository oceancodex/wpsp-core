<?php

namespace WPSPCORE\Console\Traits;

use Illuminate\Support\Facades\File;
use WPSPCORE\Funcs;

/**
 * @property Funcs $funcs
 */
trait CommandsTrait {

	public $coreNamespace = 'WPSPCORE';
	public $funcs         = null;

	/*
	 *
	 */

	public function replaceNamespaces($content) {
		$content = str_replace('{{ rootNamespace }}', $this->funcs->rootNamespace, $content);
		return str_replace('{{ coreNamespace }}', $this->coreNamespace, $content);
	}

	public function validateClassName($className = null): void {
		if (empty($className) || preg_match('/[^A-Za-z0-9_]/', $className)) {
			$this->error('[ERROR] The name: "' . $className . '" is invalid! Please try again.');
			exit();
		}
	}

	/*
	 *
	 */

	public function getRouteContent($routeName) {
		return File::get($this->funcs->mainPath . '/routes/' . $routeName . '.php');
	}

	/*
	 *
	 */

	public function saveRouteContent($routeName, $content): void {
		File::put($this->funcs->mainPath . '/routes/' . $routeName . '.php', $content);
	}

	/*
	 *
	 */

	public function addClassToRoute($routeName, $findFunction, $newLineForFindFunction, $newLineUseClass): void {
		$routeContent = $this->getRouteContent($routeName);
		$routeContent = preg_replace('/public function ' . $findFunction . '([\S\s]*?)\{/iu', 'public function ' . $findFunction . "$1{\n" . $newLineForFindFunction, $routeContent);
		if (!strpos($routeContent, $newLineUseClass) !== false) {
			$routeContent = preg_replace('/(\n\s*)class ' . $routeName . ' extends/iu', "\n" . $newLineUseClass . '$1class ' . $routeName . ' extends', $routeContent);
		}
		$this->saveRouteContent($routeName, $routeContent);
	}

}