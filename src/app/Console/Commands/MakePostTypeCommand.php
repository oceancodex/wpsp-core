<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakePostTypeCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:post-type
        {name? : The name of the post type.}';

	protected $description = 'Create a new post type.                   | Eg: bin/wpsp make:post-type custom_post_type';

	protected $help = 'This command allows you to create a post type...';

	public function handle() {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		// Ask interactively
		if (!$name) {
			$name = $this->ask('Please enter the name of the post type');

			if (empty($name)) {
				$this->error('Missing name for the post type. Please try again.');
				exit;
			}
		}

		// Validate
		$this->validateClassName($name);

		// Check exists
		$path = $mainPath . '/app/WordPress/PostTypes/' . $name . '.php';

		if (File::exists($path)) {
			$this->error('Post type: "' . $name . '" already exists! Please try again.');
			exit;
		}

		// Create class file
		$content = File::get(__DIR__ . '/../Stubs/PostTypes/posttype.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		// Func line
		$func = File::get(__DIR__ . '/../Funcs/PostTypes/posttype.func');
		$func = str_replace(['{{ name }}'], [$name], $func);

		// Use line
		$use = File::get(__DIR__ . '/../Uses/PostTypes/posttype.use');
		$use = str_replace(['{{ name }}'], [$name], $use);
		$use = $this->replaceNamespaces($use);

		// Register
		$this->addClassToRoute('PostTypes', 'post_types', $func, $use);

		// Done
		$this->info('Created new post type: "' . $name . '"');

		exit;
	}

}
