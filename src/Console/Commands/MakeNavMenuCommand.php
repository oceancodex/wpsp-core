<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeNavMenuCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:nav-menu
        {name? : The name of the navigation menu.}';

	protected $description = 'Create a new navigation menu.             | Eg: bin/wpsp make:nav-location custom_nav_location';

	protected $help = 'This command allows you to create a navigation menu.';

	public function handle(): void {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		// Ask interactively
		if (!$name) {
			$name = $this->ask('Please enter the name of the navigation menu');

			if (empty($name)) {
				$this->error('Missing name for the navigation menu. Please try again.');
				exit;
			}
		}

		// Validate
		$this->validateClassName($name);

		// Build path
		$path = $mainPath . '/app/WP/NavigationMenus/Menus/' . $name . '.php';

		// Load stub
		$content = File::get(__DIR__ . '/../Stubs/NavigationMenus/Menus/navmenu.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = $this->replaceNamespaces($content);

		// Save file
		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		// Output
		$this->info('Created new navigation menu: "' . $name . '"');

		exit;
	}

}
