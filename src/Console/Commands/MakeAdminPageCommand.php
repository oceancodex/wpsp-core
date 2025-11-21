<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeAdminPageCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:admin-page
        {path? : The path of the admin page}
        {--create-view : Create view files for this admin page}';

	protected $description = 'Create a new admin page.                  | Eg: php artisan make:admin-page custom-admin-page --create-view';

	public function handle(): void {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		$path = $this->argument('path');

		// If path is empty, ask questions.
		if (!$path) {
			$path = $this->ask('Please enter the path of the admin page');

			if (empty($path)) {
				$this->error('Missing path for the admin page. Please try again.');
				exit;
			}

			$createView = $this->confirm('Do you want to create view files for this admin page?', false);
		}
		else {
			$createView = $this->option('create-view');
		}

		// Define variables.
		$pathSlugify = Str::slug($path);
		$name        = $path;
		$nameSlugify = Str::slug($name, '_');

		// Validate class name.
		$this->validateClassName($nameSlugify);

		// Prepare paths.
		$adminClassPath = $mainPath . '/app/Components/AdminPages/' . $nameSlugify . '.php';
		$viewDirPath    = $mainPath . '/resources/views/modules/admin-pages/' . $path;

		// Check exist.
		if (File::exists($adminClassPath) || File::exists($viewDirPath)) {
			$this->error('[ERROR] Admin page "' . $path . '" already exists!');
			exit;
		}

		// Load stub.
		if ($createView) {
			$content = File::get(__DIR__ . '/../Stubs/AdminPages/adminpage-view.stub');
		}
		else {
			$content = File::get(__DIR__ . '/../Stubs/AdminPages/adminpage.stub');
		}

		// Replace placeholders.
		$content = str_replace(
			['{{ className }}', '{{ name }}', '{{ name_slugify }}', '{{ path }}', '{{ path_slugify }}'],
			[$nameSlugify, $name, $nameSlugify, $path, $pathSlugify],
			$content
		);

		$content = $this->replaceNamespaces($content);

		// Ensure directory exists.
		File::ensureDirectoryExists(dirname($adminClassPath));

		// Create class file.
		File::put($adminClassPath, $content);

		// Create view files.
		if ($createView) {
			$bladeExt    = class_exists('Illuminate\View\View') ? '.blade.php' : '.php';
			$nonBladeSep = class_exists('Illuminate\View\View') ? '' : '/non-blade';

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

		// Prepare new line for find function.
		$func = File::get(__DIR__ . '/../Funcs/AdminPages/adminpage.func');
		$func = str_replace(['{{ name }}', '{{ name_slugify }}', '{{ path }}', '{{ path_slugify }}'],
			[$name, $nameSlugify, $path, $pathSlugify],
			$func);

		// Prepare new line for use class.
		$use = File::get(__DIR__ . '/../Uses/AdminPages/adminpage.use');
		$use = str_replace(
			['{{ name }}', '{{ name_slugify }}', '{{ path }}', '{{ path_slugify }}'],
			[$name, $nameSlugify, $path, $pathSlugify],
			$use
		);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('AdminPages', 'admin_pages', $func, $use);

		$this->info("Created new admin page: {$path}");

		exit;
	}

}
