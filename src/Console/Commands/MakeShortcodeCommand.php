<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeShortcodeCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:shortcode
        {name? : The name of the shortcode.}
        {--create-view : Create view files for this shortcode}';

	// Giữ nguyên spacing trong | Eg:
	protected $description = 'Create a new shortcode.                   | Eg: bin/wpsp make:shortcode custom_shortcode --create-view';

	protected $help = 'This command allows you to create a shortcode.';

	public function handle(): void {
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		// Interactive input
		if (!$name) {
			$name = $this->ask('Please enter the name of the shortcode');

			if (empty($name)) {
				$this->error('Missing name for the shortcode. Please try again.');
				exit;
			}

			$createView = $this->confirm('Do you want to create view files for this shortcode?', false);
		}

		$nameSlugify = Str::slug($name, '_');
		$createView  = $createView ?? $this->option('create-view');

		// Paths
		$classPath = $mainPath . '/app/Components/Shortcodes/' . $nameSlugify . '.php';
		$viewPath  = $mainPath . '/resources/views/modules/shortcodes/' . $name . '.blade.php';

		// Check exists
		if (File::exists($classPath)) {
			$this->error('[ERROR] Shortcode: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/* -------------------------------------------------
		 *  CREATE VIEW (OPTIONAL)
		 * ------------------------------------------------- */
		if ($createView) {
			$view = File::get(__DIR__ . '/../Views/Shortcodes/shortcode.view');
			$view = str_replace(
				['{{ name }}', '{{ name_slugify }}'],
				[$name, $nameSlugify],
				$view
			);

			File::ensureDirectoryExists(dirname($viewPath));
			File::put($viewPath, $view);

			$stub = File::get(__DIR__ . '/../Stubs/Shortcodes/shortcode-view.stub');
		}
		else {
			$stub = File::get(__DIR__ . '/../Stubs/Shortcodes/shortcode.stub');
		}

		/* -------------------------------------------------
		 *  CREATE CLASS FILE
		 * ------------------------------------------------- */
		$stub = str_replace(
			['{{ className }}', '{{ name }}', '{{ name_slugify }}'],
			[$nameSlugify, $name, $nameSlugify],
			$stub
		);

		$stub = $this->replaceNamespaces($stub);

		File::ensureDirectoryExists(dirname($classPath));
		File::put($classPath, $stub);

		/* -------------------------------------------------
		 *  REGISTER IN route list (func + use)
		 * ------------------------------------------------- */
		$func = File::get(__DIR__ . '/../Funcs/Shortcodes/shortcode.func');
		$func = str_replace(
			['{{ name }}', '{{ name_slugify }}'],
			[$name, $nameSlugify],
			$func
		);

		$use = File::get(__DIR__ . '/../Uses/Shortcodes/shortcode.use');
		$use = str_replace(
			['{{ name }}', '{{ name_slugify }}'],
			[$name, $nameSlugify],
			$use
		);
		$use = $this->replaceNamespaces($use);

		$this->addClassToRoute('Shortcodes', 'shortcodes', $func, $use);

		/* -------------------------------------------------
		 *  DONE
		 * ------------------------------------------------- */
		$this->info('Created new shortcode: "' . $name . '"');

		exit;
	}

}
