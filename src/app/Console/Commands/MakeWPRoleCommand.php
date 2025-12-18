<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeWPRoleCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:wp-role
        {name? : The name of the role.}';

	protected $description = 'Create a new role.                        | Eg: bin/wpsp make:wp-role custom_role';

	protected $help = 'This command allows you to create a role...';

	public function handle() {
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		// Ask name interactively
		if (!$name) {
			$name = $this->ask('Please enter the name of the role');

			if (empty($name)) {
				$this->error('Missing name for the role. Please try again.');
				exit;
			}
		}

		// Validate
		$this->validateClassName($name);

		// Check exists
		$path = $mainPath . '/app/WordPress/WPRoles/' . $name . '.php';

		if (File::exists($path)) {
			$this->error('Role: "' . $name . '" already exists! Please try again.');
			exit;
		}

		// Create class file
		$content = File::get(__DIR__ . '/../Stubs/WPRoles/wprole.stub');
		$content = str_replace(
			['{{ className }}', '{{ name }}'],
			[$name, $name],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		// Func line
		$func = File::get(__DIR__ . '/../Funcs/WPRoles/wprole.func');
		$func = str_replace(
			['{{ name }}'],
			[$name],
			$func
		);

		// Use line
		$use = File::get(__DIR__ . '/../Uses/WPRoles/wprole.use');
		$use = str_replace(
			['{{ name }}'],
			[$name],
			$use
		);
		$use = $this->replaceNamespaces($use);

		// Register class
		$this->addClassToRoute('Roles', 'roles', $func, $use);

		// Done
		$this->info('Created new role: "' . $name . '"');

		exit;
	}

}
