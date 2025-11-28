<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeNavLocationCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:nav-location
        {name? : The name of the navigation menu location.}';

	protected $description = 'Create a new navigation menu location.    | Eg: bin/wpsp make:nav-location custom_nav_location';

	protected $help = 'This command allows you to create a navigation menu location.';

	public function handle(): void {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		// Ask interactively
		if (!$name) {
			$name = $this->ask('Please enter the name of the navigation menu location');

			if (empty($name)) {
				$this->error('Missing name for the navigation menu location. Please try again.');
				exit;
			}
		}

		$nameSlugify = Str::slug($name, '_');

		// Validate class name
		$this->validateClassName($nameSlugify);

		// Path for class file
		$path = $mainPath . '/app/WP/NavigationMenus/Locations/' . $nameSlugify . '.php';

		// Create content
		$content = File::get(__DIR__ . '/../Stubs/NavigationMenus/Locations/navlocation.stub');
		$content = str_replace('{{ className }}', $nameSlugify, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		// Build func line
		$func = File::get(__DIR__ . '/../Funcs/NavigationMenus/Locations/navlocation.func');
		$func = str_replace(['{{ name }}', '{{ name_slugify }}'], [$name, $nameSlugify], $func);

		// Build use line
		$use = File::get(__DIR__ . '/../Uses/NavigationMenus/Locations/navlocation.use');
		$use = str_replace(['{{ name }}', '{{ name_slugify }}'], [$name, $nameSlugify], $use);
		$use = $this->replaceNamespaces($use);

		// Add to route
		$this->addClassToRoute('NavLocations', 'nav_locations', $func, $use);

		// Output
		$this->info('Created new navigation menu location: "' . $name . '"');

		exit;
	}

}
