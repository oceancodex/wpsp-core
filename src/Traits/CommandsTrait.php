<?php

namespace OCBPCORE\Traits;

use OCBPCORE\Objects\File\FileHandler;
use Symfony\Component\Console\Command\Command;

trait CommandsTrait {

	public string $rootNamespace = 'OCBP';

	public function replaceRootNamespace($content): array|string {
		return str_replace('{{ rootNamespace }}', $this->rootNamespace, $content);
	}

	public function getWebRouteContent(): string {
		$webRoute = FileHandler::getFileSystem()->get(__DIR__ . '/../../routes/WebRoute.php');
		return $webRoute;
	}

	public function saveWebRouteContent($webRouteContent): void {
		FileHandler::saveFile($webRouteContent, __DIR__ . '/../../routes/WebRoute.php');
	}

	public function addClassToWebRoute($findFunction, $newLineForFindFunction, $newLineUseClass): void {
		$webRouteContent = $this->getWebRouteContent();
		if (strpos($webRouteContent, $newLineUseClass) !== false) return;
		$webRouteContent = preg_replace('/public function '.$findFunction.'([\S\s]*?)\{([\S\s]*?)}/iu', 'public function '.$findFunction.'$1{$2' . $newLineForFindFunction . "\n	}", $webRouteContent);
		$webRouteContent = preg_replace('/(\n\s*)class WebRoute extends/iu', "\n" . $newLineUseClass . '$1class WebRoute extends', $webRouteContent);
        $this->saveWebRouteContent($webRouteContent);
	}

	public function validateClassName($output, $className = null): void {
		if (empty($className) || preg_match('/[^A-Za-z0-9_]/', $className)) {
			$output->writeln('[ERROR] The name: "'.$className.'" is invalid! Please try again.');
			exit(Command::INVALID);
		}
	}

}