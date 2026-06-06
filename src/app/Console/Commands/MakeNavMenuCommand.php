<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeNavMenuCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:nav-menu
        {name? : The name of the navigation menu.}';

	protected $description = 'Create a new navigation menu. | Eg: php artisan make:nav-menu custom_nav_menu';

	protected $help = 'This command allows you to create a navigation menu.';

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
			$name = $this->ask('Please enter the name of the navigation menu (Eg: custom_nav)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the navigation menu. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($name);

		// Chuẩn bị thêm các biến để sử dụng.
		$className = Str::slug($name, '_');

		// Kiểm tra tồn tại.
		$path = $mainPath . '/app/WordPress/NavigationMenus/Menus/' . $className . '.php';

		if (File::exists($path)) {
			$this->error('Navigation menu: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/NavigationMenus/Menus/navmenu.stub');
		$content = str_replace(
			['{{ class_name }}', '{{ name }}'],
			[$className, $name],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		// Done.
		$this->info('Created new navigation menu: "' . $name . '"');

		exit;
	}

}
