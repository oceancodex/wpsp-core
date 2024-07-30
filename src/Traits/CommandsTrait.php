<?php

namespace WPSPCORE\Traits;

use Filesystem;
use Symfony\Component\Console\Command\Command;
use WPSPCORE\Funcs;

trait CommandsTrait {

	public ?string $mainPath      = null;
	public ?string $rootNamespace = null;
	public ?string $prefixEnv     = null;
	public ?Funcs  $funcs         = null;
	public string  $coreNamespace = 'WPSPCORE';

	public function __construct(?string $name = null, $mainPath = null, $rootNamespace = null, $prefixEnv = null) {
		parent::__construct($name);
		$this->mainPath      = $mainPath;
		$this->rootNamespace = $rootNamespace;
		$this->prefixEnv     = $prefixEnv;
		$this->funcs         = new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv);
	}

	public function replaceNamespaces($content) {
		$content = str_replace('{{ rootNamespace }}', $this->rootNamespace, $content);
		return str_replace('{{ coreNamespace }}', $this->coreNamespace, $content);
	}

	public function validateClassName($output, $className = null): void {
		if (empty($className) || preg_match('/[^A-Za-z0-9_]/', $className)) {
			$output->writeln('[ERROR] The name: "' . $className . '" is invalid! Please try again.');
			exit(Command::INVALID);
		}
	}

	/*
	 *
	 */

	public function getRouteContent($routeName): string {
		return Filesystem::get($this->mainPath . '/routes/'.$routeName.'.php');
	}

	/*
	 *
	 */

	public function saveRouteContent($routeName, $content): void {
		Filesystem::instance()->put($this->mainPath . '/routes/'.$routeName.'.php', $content);
	}

	/*
	 *
	 */

	public function addClassToRoute($routeName, $findFunction, $newLineForFindFunction, $newLineUseClass): void {
		$routeContent = $this->getRouteContent($routeName);
		$routeContent = preg_replace('/public function ' . $findFunction . '([\S\s]*?)\{/iu', 'public function ' . $findFunction . "$1{\n" . $newLineForFindFunction, $routeContent);
		if (!strpos($routeContent, $newLineUseClass) !== false) {
			$routeContent = preg_replace('/(\n\s*)class '.$routeName.' extends/iu', "\n" . $newLineUseClass . '$1class '.$routeName.' extends', $routeContent);
		}
		$this->saveRouteContent($routeName, $routeContent);
	}

}