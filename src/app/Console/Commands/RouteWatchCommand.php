<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use WPSP\Funcs;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class RouteWatchCommand extends Command {

	use CommandsTrait;

	/**
	 * Laravel-style signature
	 */
	protected $signature = 'route:watch 
        {--ide= : Choose IDE to auto-reload. Supported: phpstorm}';

	protected $description = 'Watch route files & auto remap. Eg: bin/wpsp route:watch';

	public function handle() {
		$this->funcs = $this->laravel['funcs'] ?? null;

		if (!$this->funcs) {
			return;
		}

		$mainPath = $this->funcs->_getMainPath();
		$watchDir = $mainPath . '/routes';
		$ide      = strtolower($this->option('ide'));
		$ideStr   = $ide ? " --ide={$ide}" : null;

		if (!is_dir($watchDir)) {
			$this->error("Directory not found: {$watchDir}");
		}

		$this->info("Route remap watching: {$watchDir}");
		if ($ide) {
			$this->line("IDE: {$ide}");
		}

		$hashes = $this->scan($watchDir);

		while (true) {
			sleep(1);

			$newHashes = $this->scan($watchDir);

			if ($newHashes !== $hashes) {
				$hashes = $newHashes;

				$this->line('<fg=yellow>Change detected. Remapping...</>');

				exec(
					'php ' . escapeshellarg($mainPath . '/artisan')
					. ' route:remap'
					. $ideStr
				);

				$this->info('Remap routes successfully!');
				$this->info('Watching...');
			}
		}
	}

	private function scan($dir) {
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
