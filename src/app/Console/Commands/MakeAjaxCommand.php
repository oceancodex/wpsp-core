<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeAjaxCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:ajax
        {action? : The action name of the Ajax}
        {--method : The HTTP method of the Ajax (GET, POST, PUT, DELETE)}
        {--nopriv : Allow access for non-logged users}';

	protected $description = 'Create a new Ajax action. | Eg: php artisan make:ajax GET my_action --nopriv';

	public function handle() {
		$this->funcs = $this->getLaravel()->make('funcs');

		$action = $this->argument('action');
		$method = $this->option('method');
		$nopriv = $this->option('nopriv');

		// If no action provided → interactive mode
		if (!$action) {
			$action = $this->ask('Please enter the action name of the ajax (Eg: my_action)');

			if (empty($action)) {
				$this->error('Missing action name for the ajax. Please try again.');
				exit;
			}

			$method = $this->ask('Please enter the HTTP method of the ajax (Eg: GET, POST or get, post... Default: GET)');
			$nopriv = $this->confirm('Do you want to allow access for non-logged users (nopriv)?', false);
		}

		// Define variables
		$method = strtolower($method ?: 'GET');

		// Validate
		$this->validateClassName($action);

		// Prepare line for find function
		$func = $nopriv ? File::get(__DIR__ . '/../Funcs/Ajaxs/ajax-nopriv.func') : File::get(__DIR__ . '/../Funcs/Ajaxs/ajax.func');
		$func = str_replace(
			['{{ method }}', '{{ action }}', '{{ nopriv }}'],
			[$method, $action, $nopriv],
			$func
		);

		// Prepare line for use class
		$use = File::get(__DIR__ . '/../Uses/Ajaxs/ajax.use');
		$use = str_replace(
			['{{ method }}', '{{ action }}', '{{ nopriv }}'],
			[$method, $action, $nopriv],
			$use
		);

		$use = $this->replaceNamespaces($use);

		// Add to routes
		$this->addClassToRoute('Ajaxs', 'ajaxs', $func, $use);

		// Output
		$this->info("Created new Ajax action: {$action}");

		exit;
	}

}
