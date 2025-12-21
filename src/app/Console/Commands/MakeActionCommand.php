<?php

namespace WPSPCORE\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeActionCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:action
        {action? : The action hook}';

	protected $description = 'Create a new action hook. | Eg: php artisan make:action wp_head';

	public function handle() {
		$action = $this->argument('action');

		// If no action provided → interactive mode
		if (!$action) {
			$action = $this->ask('Please enter the action hook (Eg: wp_head)');

			if (empty($action)) {
				$this->error('Missing action hook. Please try again.');
				exit;
			}
		}

		// Validate
		$this->validateClassName($action);

		// Prepare line for find function
		$func = File::get(__DIR__ . '/../Funcs/Actions/action.func');
		$func = str_replace(
			['{{ action }}'],
			[$action],
			$func
		);

		// Prepare line for use class
		$use = File::get(__DIR__ . '/../Uses/Actions/action.use');
		$use = $this->replaceNamespaces($use);

		// Add to routes
		$this->addClassToRoute('Actions', 'actions', $func, $use);

		// Output
		$this->info("Created new action hook: {$action}");

		exit;
	}

}
