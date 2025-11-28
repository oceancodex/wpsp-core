<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeRoleCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:role
        {name? : The name of the role.}';

	// Giữ nguyên spacing trước | Eg:
	protected $description = 'Create a new role.                        | Eg: bin/wpsp make:role custom_role';

	protected $help = 'This command allows you to create a role...';

	public function handle(): void {
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

		// Normalize
		$nameSlugify = Str::slug($name, '_');

		// Check exists
		$path = $mainPath . '/app/WP/Roles/' . $nameSlugify . '.php';

		if (File::exists($path)) {
			$this->error('[ERROR] Role: "' . $name . '" already exists! Please try again.');
			exit;
		}

		// Create class file
		$content = File::get(__DIR__ . '/../Stubs/Roles/role.stub');
		$content = str_replace(
			['{{ className }}', '{{ name }}', '{{ name_slugify }}'],
			[$nameSlugify, $name, $nameSlugify],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		// Func line
		$func = File::get(__DIR__ . '/../Funcs/Roles/role.func');
		$func = str_replace(
			['{{ name }}', '{{ name_slugify }}'],
			[$name, $nameSlugify],
			$func
		);

		// Use line
		$use = File::get(__DIR__ . '/../Uses/Roles/role.use');
		$use = str_replace(
			['{{ name }}', '{{ name_slugify }}'],
			[$name, $nameSlugify],
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
