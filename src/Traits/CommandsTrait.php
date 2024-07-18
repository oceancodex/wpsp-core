<?php

namespace WPSPCORE\Traits;

use Symfony\Component\Console\Command\Command;
use WPSPCORE\Filesystem\Filesystem;
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

	public function replaceNamespaces($content): array|string {
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

	public function getWebRouteContent(): string {
		return Filesystem::get($this->mainPath . '/routes/WebRoute.php');
	}

	public function getApiRouteContent(): string {
		return Filesystem::get($this->mainPath . '/routes/ApiRoute.php');
	}

	public function getAjaxRouteContent(): string {
		return Filesystem::get($this->mainPath . '/routes/AjaxRoute.php');
	}

	/*
	 *
	 */

	public function saveWebRouteContent($content): void {
		Filesystem::instance()->put($this->mainPath . '/routes/WebRoute.php', $content);
	}

	public function saveApiRouteContent($content): void {
		Filesystem::instance()->put($this->mainPath . '/routes/ApiRoute.php', $content);
	}

	public function saveAjaxRouteContent($content): void {
		Filesystem::instance()->put($this->mainPath . '/routes/AjaxRoute.php', $content);
	}

	/*
	 *
	 */

	public function addClassToWebRoute($findFunction, $newLineForFindFunction, $newLineUseClass): void {
		$webRouteContent = $this->getWebRouteContent();
		$webRouteContent = preg_replace('/public function ' . $findFunction . '([\S\s]*?)\{/iu', 'public function ' . $findFunction . "$1{\n" . $newLineForFindFunction, $webRouteContent);
		if (!strpos($webRouteContent, $newLineUseClass) !== false) {
			$webRouteContent = preg_replace('/(\n\s*)class WebRoute extends/iu', "\n" . $newLineUseClass . '$1class WebRoute extends', $webRouteContent);
		}
		$this->saveWebRouteContent($webRouteContent);
	}

	public function addClassToApiRoute($findFunction, $newLineForFindFunction, $newLineUseClass): void {
		$apiRouteContent = $this->getApiRouteContent();
		$apiRouteContent = preg_replace('/public function ' . $findFunction . '([\S\s]*?)\{/iu', 'public function ' . $findFunction . "$1{\n" . $newLineForFindFunction, $apiRouteContent);
		if (!strpos($apiRouteContent, $newLineUseClass) !== false) {
			$apiRouteContent = preg_replace('/(\n\s*)class ApiRoute extends/iu', "\n" . $newLineUseClass . '$1class ApiRoute extends', $apiRouteContent);
		}
		$this->saveApiRouteContent($apiRouteContent);
	}

	public function addClassToAjaxRoute($findFunction, $newLineForFindFunction, $newLineUseClass): void {
		$ajaxRouteContent = $this->getAjaxRouteContent();
		$ajaxRouteContent = preg_replace('/public function ' . $findFunction . '([\S\s]*?)\{/iu', 'public function ' . $findFunction . "$1{\n" . $newLineForFindFunction, $ajaxRouteContent);
		if (!strpos($ajaxRouteContent, $newLineUseClass) !== false) {
			$ajaxRouteContent = preg_replace('/(\n\s*)class AjaxRoute extends/iu', "\n" . $newLineUseClass . '$1class AjaxRoute extends', $ajaxRouteContent);
		}
		$this->saveAjaxRouteContent($ajaxRouteContent);
	}

}