<?php

namespace WPSPCORE\Console\Traits;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use WPSPCORE\Funcs;

/**
 * @property Funcs $funcs
 */
trait CommandsTrait {

	public $mainPath      = null;
	public $rootNamespace = null;
	public $prefixEnv     = null;

	public $funcs         = null;

	public $coreNamespace = 'WPSPCORE';

	public $extraParams   = [];

	/**
	 * $extraParams = ['funcs', 'application', 'environment']
	 */
	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		parent::__construct();
		$this->mainPath      = $mainPath;
		$this->rootNamespace = $rootNamespace;
		$this->prefixEnv     = $prefixEnv;

		$this->funcs         = $extraParams['funcs'] ?? null;
		$this->customProperties();
	}

	/*
	 *
	 */
	protected function writeln(OutputInterface $output, string $message): void {
		$style = new OutputFormatterStyle(null, 'green');
		$output->getFormatter()->setStyle('success', $style);

		$style = new OutputFormatterStyle('green');
		$output->getFormatter()->setStyle('green', $style);

		$style = new OutputFormatterStyle('red');
		$output->getFormatter()->setStyle('red', $style);

		$style = new OutputFormatterStyle('yellow');
		$output->getFormatter()->setStyle('yellow', $style);

		$tz = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('Asia/Ho_Chi_Minh');
		$dt = new \DateTime('now', $tz);

		$timestamp = '[' . $dt->format('Y-m-d H:i:s') . ']';

		$output->writeln("{$timestamp} {$message}");
	}

	/*
	 *
	 */

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

	public function customProperties() {}

	/*
	 *
	 */

	public function getRouteContent($routeName) {
		return FileSystem::get($this->mainPath . '/routes/' . $routeName . '.php');
	}

	/*
	 *
	 */

	public function saveRouteContent($routeName, $content): void {
		FileSystem::put($this->mainPath . '/routes/' . $routeName . '.php', $content);
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