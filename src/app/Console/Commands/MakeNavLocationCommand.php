<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeNavLocationCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:nav-location
        {name? : The name of the navigation menu location.}';

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
		$name = $this->argument('name');

		// Nếu không khai báo, hãy hỏi.
		if (!$name) {
			$name = $this->ask('Please enter the name of the navigation menu location (Eg: custom_nav_location)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the navigation menu location. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($name);

		// Chuẩn bị thêm các biến để sử dụng.
		$path = $mainPath . '/app/WordPress/NavigationMenus/Locations/' . $name . '.php';

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/NavigationMenus/Locations/navlocation.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/NavigationMenus/Locations/navlocation.func');
		$func = str_replace(['{{ name }}'], [$name], $func);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/NavigationMenus/Locations/navlocation.use');
		$use = str_replace(['{{ name }}'], [$name], $use);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('NavLocations', 'nav_locations', $func, $use);

		// Done.
		$this->info('Created new navigation menu location: "' . $name . '"');

		exit;
	}

}
