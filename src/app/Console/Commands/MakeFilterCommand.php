<?php

namespace WPSPCORE\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeFilterCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:filter
        {filter? : The filter hook}';

	protected $description = 'Create a new filter hook. | Eg: php artisan make:filter the_content';

	public function handle() {
		$this->funcs = $this->getLaravel()->make('funcs');

		$filter = $this->argument('filter');

		// If no filter provided → interactive mode
		if (!$filter) {
			$filter = $this->ask('Please enter the filter hook (Eg: the_content)');

			if (empty($filter)) {
				$this->error('Missing filter hook. Please try again.');
				exit;
			}
		}

		// Validate
		$this->validateClassName($filter);

		// Prepare line for find function
		$func = File::get(__DIR__ . '/../Funcs/Filters/filter.func');
		$func = str_replace(
			['{{ filter }}'],
			[$filter],
			$func
		);

		// Prepare line for use class
		$use = File::get(__DIR__ . '/../Uses/Filters/filter.use');
		$use = $this->replaceNamespaces($use);

		// Add to routes
		$this->addClassToRoute('Filters', 'filters', $func, $use);

		// Output
		$this->info("Created new filter hook: {$filter}");

		exit;
	}

}
