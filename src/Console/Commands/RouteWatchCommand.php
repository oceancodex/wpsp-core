<?php

namespace WPSPCORE\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\Console\Traits\CommandsTrait;

class RouteWatchCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('route:watch')
			->setDescription('Watch route files & auto remap.           | Eg: bin/wpsp route:watch')
			->setHelp('Automatically remap routes when files in /routes directory are updated.')
			->addOption('ide', null, InputOption::VALUE_OPTIONAL, 'Choose IDE to auto-reload. Supported: phpstorm');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$watchDir              = $this->funcs->_getMainPath('/routes');
		$ide                   = strtolower($input->getOption('ide') ?? '');
		$ideStr                = $ide ? " --ide={$ide}" : null;
		$ignoreActivePluginStr = ' --ignore-active-plugin=true';

		if (!is_dir($watchDir)) {
			$this->writeln($output, "<error>Directory not found: {$watchDir}</error>");
			return Command::INVALID;
		}

		$this->writeln($output, "<info>Route remap watching: {$watchDir}</info>");
		if ($ide) $this->writeln($output, "IDE: {$ide}");

		$hashes = $this->scan($watchDir);

		while (true) {
			sleep(1);
			$newHashes = $this->scan($watchDir);
			if ($newHashes !== $hashes) {
				$hashes = $newHashes;
				$this->writeln($output, "<yellow>Change detected. Remapping...</yellow>");
				exec('php ' . escapeshellarg($this->funcs->_getMainPath('/bin/wpsp')) . ' route:remap' . $ideStr . $ignoreActivePluginStr);
				$this->writeln($output, "<green>Remap routes successfully!</green>");
				$this->writeln($output, "<green>Watching...</green>");
			}
		}
	}

	private function scan(string $dir): array {
		$results = [];

		foreach (glob($dir . '/*.php') as $file) {
			$hash = @sha1_file($file);
			if ($hash) {
				$results[$file] = $hash;
			}
		}

		return $results;
	}

}
