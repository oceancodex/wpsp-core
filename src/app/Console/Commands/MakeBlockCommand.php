<?php

namespace WPSPCORE\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeBlockCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:block
        {name? : The block name}';

	protected $description = 'Create a new block. | Eg: php artisan make:block custom-block';

	public function handle() {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;
		$textDomain  = $this->funcs->_getTextDomain();

		// Define variables
		$name = $this->argument('name');

		// Interactive mode
		if (!$name) {
			$name = $this->ask('Please enter the block name (Eg: custom-block)');

			if (empty($name)) {
				$this->error('Missing block name. Please try again.');
				exit;
			}
		}

		// Normalize variables
		$className = str_replace('-', '_', $name);
		$className = Str::slug($className, '_');

		// Validate
		$this->validateClassName($className);

		// Prepare paths.
		$adminClassPath = $mainPath . '/app/WordPress/Blocks/' . $className . '.php';
		$viewDirPath    = $mainPath . '/resources/views/blocks/src/' . $name;

		// Check exist.
		if (File::exists($adminClassPath) || File::exists($viewDirPath)) {
			$this->error('The block "' . $name . '" already exists!');
			exit;
		}

		/**
		 * ---
		 * Create class file
		 * ---
		 */

		$content = File::get(__DIR__ . '/../Stubs/Blocks/block.stub');
		$content = str_replace(
			['{{ name }}', '{{ className }}'],
			[$name, $className],
			$content
		);
		$content = $this->replaceNamespaces($content);

		// Ensure directory exists.
		File::ensureDirectoryExists(dirname($adminClassPath));

		// Create class file.
		File::put($adminClassPath, $content);

		/**
		 * ---
		 * Create view files
		 * ---
		 */

		File::ensureDirectoryExists($viewDirPath);

		$viewFiles = [
			'block.json',
			'edit.js',
			'editor.scss',
			'index.js',
			'render.php',
			'script.js',
			'style.scss',
			'view.js',
		];

		foreach ($viewFiles as $viewFile) {
			$view = File::get(__DIR__ . '/../Stubs/Blocks/' . $viewFile);

			$view = str_replace(
				['{{ name }}', '{{ className }}', '{{ textDomain }}'],
				[$name, $className, $textDomain],
				$view
			);

			$view = $this->replaceNamespaces($view);

			File::put($viewDirPath . "/{$viewFile}", $view);
		}

		// Prepare line for find function
		$func = File::get(__DIR__ . '/../Funcs/Blocks/block.func');
		$func = str_replace(
			['{{ name }}', '{{ className }}'],
			[$name, $className],
			$func
		);

		// Prepare line for use class
		$use = File::get(__DIR__ . '/../Uses/Blocks/block.use');
		$use = str_replace(
			['{{ name }}', '{{ className }}'],
			[$name, $className],
			$use
		);
		$use = $this->replaceNamespaces($use);

		// Add to routes
		$this->addClassToRoute('Blocks', 'blocks', $func, $use);

		// Output
		$this->info("Created new action hook: {$name}");

		exit;
	}

}
