<?php

namespace WPSPCORE\Traits;

use Symfony\Component\Console\Command\Command;
use WPSPCORE\Filesystem\Filesystem;

trait CommandsTrait {

	public ?string $mainPath      = null;
	public ?string $rootNamespace = null;
	public string  $coreNamespace = 'WPSPCORE';

	public function __construct(?string $name = null, $rootNamespace = null, $mainPath = null) {
		parent::__construct($name);
		$this->rootNamespace = $rootNamespace;
		$this->mainPath      = $mainPath;
	}

	public function replaceNamespaces($content): array|string {
		$content = str_replace('{{ rootNamespace }}', $this->rootNamespace, $content);
		return str_replace('{{ coreNamespace }}', $this->coreNamespace, $content);
	}

	public function getWebRouteContent(): string {
		$webRoute = Filesystem::instance()->get($this->mainPath . '/routes/WebRoute.php');
		return $webRoute;
	}

	public function saveWebRouteContent($webRouteContent): void {
		Filesystem::instance()->put($this->mainPath . '/routes/WebRoute.php', $webRouteContent);
	}

	public function addClassToWebRoute($findFunction, $newLineForFindFunction, $newLineUseClass): void {
		$webRouteContent = $this->getWebRouteContent();
		if (strpos($webRouteContent, $newLineUseClass) !== false) return;
		$webRouteContent = preg_replace('/public function ' . $findFunction . '([\S\s]*?)\{([\S\s]*?)}/iu', 'public function ' . $findFunction . '$1{$2' . $newLineForFindFunction . "\n	}", $webRouteContent);
		$webRouteContent = preg_replace('/(\n\s*)class WebRoute extends/iu', "\n" . $newLineUseClass . '$1class WebRoute extends', $webRouteContent);
		$this->saveWebRouteContent($webRouteContent);
	}

	public function validateClassName($output, $className = null): void {
		if (empty($className) || preg_match('/[^A-Za-z0-9_]/', $className)) {
			$output->writeln('[ERROR] The name: "' . $className . '" is invalid! Please try again.');
			exit(Command::INVALID);
		}
	}

}