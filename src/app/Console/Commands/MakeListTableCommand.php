<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeListTableCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:list-table
        {name? : The name of the list table.}';

	protected $description = 'Create a new list table.                  | Eg: bin/wpsp make:list-table MyListTable';

	protected $help = 'This command allows you to create a list table.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		// Ask interactively if missing
		if (!$name) {
			$name = $this->ask('Please enter the name of the list table');

			if (empty($name)) {
				$this->error('Missing name for the list table. Please try again.');
				exit;
			}
		}

		// Validate class name
		$this->validateClassName($name);

		// Build path
		$path = $mainPath . '/app/WordPress/ListTables/' . $name . '.php';

		// Check exists? (FileSystem trước đây không check)
		// Nếu muốn check trùng, uncomment:
		// if (File::exists($path)) {
		//     $this->error('List table: "' . $name . '" already exists! Please try again.');
		//     exit;
		// }

		// Load stub
		$content = File::get(__DIR__ . '/../Stubs/ListTables/listtable.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = $this->replaceNamespaces($content);

		// Ensure directory exists
		File::ensureDirectoryExists(dirname($path));

		// Create file
		File::put($path, $content);

		// Output
		$this->info('Created new list table: "' . $name . '"');

		exit;
	}

}
