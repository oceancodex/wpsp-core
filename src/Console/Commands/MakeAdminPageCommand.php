<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeAdminPageCommand extends Command {

	protected $signature = 'make:admin-page
        {path? : The path of the admin page}
        {--create-view : Create view files for this admin page}';

	protected $description = 'Create a new admin page. Example: php artisan make:admin-page custom-admin-page --create-view';

	public function handle(): void {
		$path = $this->argument('path');

		// Ask for missing path
		if (!$path) {
			$path = $this->ask('Please enter the path of the admin page');

			if (empty($path)) {
				$this->error('Missing path for the admin page. Please try again.');
			}

			$createView = $this->confirm('Do you want to create view files for this admin page?', false);
		}
		else {
			$createView = $this->option('create-view');
		}

		// Base variables
		$pathSlugify = Str::slug($path);
		$name        = $path;
		$nameSlugify = Str::slug($name, '_');

		// Base paths
		$mainPath       = base_path(); // hoặc thay bằng $this->mainPath nếu bạn có trong hệ thống
		$adminClassPath = $mainPath . '/app/Components/AdminPages/' . $nameSlugify . '.php';
		$viewDirPath    = $mainPath . '/resources/views/modules/admin-pages/' . $path;

		// Check exist
		if (File::exists($adminClassPath) || File::exists($viewDirPath)) {
			$this->error('[ERROR] Admin page "' . $path . '" already exists!');
		}

		// Load stub
		if ($createView) {
			$content = File::get(__DIR__ . '/../Stubs/AdminPages/adminpage-view.stub');
		}
		else {
			$content = File::get(__DIR__ . '/../Stubs/AdminPages/adminpage.stub');
		}

		// Replace placeholders
		$content = str_replace(
			['{{ className }}', '{{ name }}', '{{ name_slugify }}', '{{ path }}', '{{ path_slugify }}'],
			[$nameSlugify, $name, $nameSlugify, $path, $pathSlugify],
			$content
		);

		// Ensure directory exists
		File::ensureDirectoryExists(dirname($adminClassPath));

		// Write class file
		File::put($adminClassPath, $content);

		// Handle view generation
		if ($createView) {
			$bladeExt    = class_exists('\WPSPCORE\View\Blade') ? '.blade.php' : '.php';
			$nonBladeSep = class_exists('\WPSPCORE\View\Blade') ? '' : '/non-blade';

			File::ensureDirectoryExists($viewDirPath);

			$viewFiles = [
				'main'       => 'adminpage.view',
				'dashboard'  => 'dashboard.view',
				'tab-1'      => 'tab-1.view',
				'navigation' => 'navigation.view',
			];

			foreach ($viewFiles as $filename => $stub) {
				$view = File::get(__DIR__ . '/../Views/AdminPages' . $nonBladeSep . '/' . $stub);

				$view = str_replace(
					['{{ name }}', '{{ name_slugify }}', '{{ path }}', '{{ path_slugify }}'],
					[$name, $nameSlugify, $path, $pathSlugify],
					$view
				);

				File::put($viewDirPath . "/{$filename}{$bladeExt}", $view);
			}
		}

		// Add class to routes if needed
		$func = File::get(__DIR__ . '/../Funcs/AdminPages/adminpage.func');
		$func = str_replace(['{{ name }}', '{{ name_slugify }}', '{{ path }}', '{{ path_slugify }}'],
			[$name, $nameSlugify, $path, $pathSlugify],
			$func);

		$use = File::get(__DIR__ . '/../Uses/AdminPages/adminpage.use');
		$use = str_replace(['{{ name }}', '{{ name_slugify }}', '{{ path }}', '{{ path_slugify }}'],
			[$name, $nameSlugify, $path, $pathSlugify],
			$use);

		// Nếu bạn có hàm route register riêng, bạn tự thêm ở đây:
		// $this->addClassToRoute(...)

		$this->info("Created new admin page: {$path}");
	}

}
