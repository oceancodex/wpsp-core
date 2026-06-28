<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeNavLocationCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:nav-location
        {location? : The name of the navigation menu location.}';

	protected $description = 'Create a new navigation menu location. | Eg: php artisan make:nav-location custom_nav_location';

	protected $help = 'This command allows you to create a navigation menu location.';

	public function handle() {
		/**
		 * ---
		 * Funcs.
		 * ---
		 */
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		/**
		 * ---
		 * Khai báo, hỏi và kiểm tra.
		 * ---
		 */
		$location = $this->argument('location');

		// Nếu không khai báo, hãy hỏi.
		if (!$location) {
			$location = $this->ask('Please enter the location of the navigation menu location (Eg: custom_nav_location)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($location)) {
				$this->error('Missing location for the navigation menu location. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($location);

		// Chuẩn bị thêm các biến để sử dụng.
		$className = Str::slug($location, '_');

		// Kiểm tra tồn tại.
		$path = $mainPath . '/app/WordPress/NavigationMenus/Locations/' . $className . '.php';

		if (File::exists($path)) {
			$this->error('Navigation menu location: "' . $location . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/NavigationMenus/Locations/nav-location.stub');
		$content = str_replace(
			['{{ location }}', '{{ class_name }}'],
			[$location, $className],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/NavigationMenus/Locations/nav-location.func');
		$func = str_replace(
			['{{ location }}', '{{ class_name }}'],
			[$location, $className],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/NavigationMenus/Locations/nav-location.use');
		$use = str_replace(
			['{{ location }}', '{{ class_name }}'],
			[$location, $className],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('NavLocations', 'nav_locations', $func, $use);

		// Done.
		$this->info('Created new navigation menu location: "' . $location . '"');

		exit;
	}

}
