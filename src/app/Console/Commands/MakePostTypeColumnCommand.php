<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakePostTypeColumnCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:post-type-column
        {name? : The name of the post type column.}';

	protected $description = 'Create a new post type column. | Eg: php artisan make:post-type-column my_custom_column';

	protected $help = 'This command allows you to create a custom column for post type list table.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		// Ask interactively if not provided
		if (!$name) {
			$name = $this->ask('Please enter the name of the post type column (Eg: my_custom_column)');

			if (empty($name)) {
				$this->error('Missing name for the post type column. Please try again.');
				exit;
			}
		}

		// Validate class name
		$this->validateClassName($name);

		// Path
		$path = $mainPath . '/app/WordPress/PostTypeColumns/' . $name . '.php';

		// Check exists
		if (File::exists($path)) {
			$this->error('Post type column: "' . $name . '" already exists! Please try again.');
			exit;
		}

		// Create class file
		$stub = File::get(__DIR__ . '/../Stubs/PostTypeColumns/post_type_column.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $stub);

		// Func line
		$func = File::get(__DIR__ . '/../Funcs/PostTypeColumns/post_type_column.func');
		$func = str_replace(
			['{{ name }}'],
			[$name],
			$func
		);

		// Use line
		$use = File::get(__DIR__ . '/../Uses/PostTypeColumns/post_type_column.use');
		$use = str_replace(
			['{{ name }}'],
			[$name],
			$use
		);
		$use = $this->replaceNamespaces($use);

		// Register
		$this->addClassToRoute('PostTypeColumns', 'post_type_columns', $func, $use);

		// Done
		$this->info('Created new post type column: "' . $name . '"');

		exit;
	}

}
