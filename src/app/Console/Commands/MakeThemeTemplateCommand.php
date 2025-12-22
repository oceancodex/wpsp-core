<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeThemeTemplateCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:theme-template
        {name? : The name of theme template.}
        {--post-type= : The post type for theme template.}';

	protected $description = 'Create a new theme template. | Eg: php artisan make:theme-template custom_theme_template --post-type=page';

	protected $help = 'This command allows you to create a theme template.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		/* -------------------------------------------------
		 *  Ask interactive
		 * ------------------------------------------------- */
		if (!$name) {
			$name = $this->ask('Please enter the name of theme template (Eg: custom_theme_template)');

			if (empty($name)) {
				$this->error('Missing name for theme template. Please try again.');
				exit;
			}

			$postType = $this->ask('Please enter the post type for theme template', 'page');
		}

		// Define variables
		$postType = $postType ?? $this->option('post-type') ?: 'page';

		// Validate
		$this->validateClassName($name);

		/* -------------------------------------------------
		 *  Check exists
		 * ------------------------------------------------- */
		$classPath = $mainPath . '/app/WordPress/ThemeTemplates/' . $name . '.php';
		$viewPath  = $mainPath . '/resources/views/theme-templates/' . $name . '.php';

		if (File::exists($classPath)) {
			$this->error('Template: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/* -------------------------------------------------
		 *  CREATE CLASS FILE
		 * ------------------------------------------------- */
		$content = File::get(__DIR__ . '/../Stubs/ThemeTemplates/theme-template.stub');
		$content = str_replace(
			['{{ className }}', '{{ name }}', '{{ postType }}'],
			[$name, $name, $postType],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($classPath));
		File::put($classPath, $content);

		/* -------------------------------------------------
		 *  CREATE VIEW FILE
		 * ------------------------------------------------- */
		$view = File::get(__DIR__ . '/../Views/ThemeTemplates/theme-template.view');
		$view = str_replace(
			['{{ name }}', '{{ postType }}'],
			[$name, $postType],
			$view
		);

		File::ensureDirectoryExists(dirname($viewPath));
		File::put($viewPath, $view);

		/* -------------------------------------------------
		 *  REGISTER into ROUTE (func + use)
		 * ------------------------------------------------- */
		$func = File::get(__DIR__ . '/../Funcs/ThemeTemplates/theme-template.func');
		$func = str_replace(
			['{{ name }}', '{{ postType }}'],
			[$name, $postType],
			$func
		);

		$use = File::get(__DIR__ . '/../Uses/ThemeTemplates/theme-template.use');
		$use = str_replace(
			['{{ name }}', '{{ postType }}'],
			[$name, $postType],
			$use
		);
		$use = $this->replaceNamespaces($use);

		$this->addClassToRoute('ThemeTemplates', 'theme_templates', $func, $use);

		/* -------------------------------------------------
		 *  DONE
		 * ------------------------------------------------- */
		$this->info('Created new theme template: "' . $name . '"');

		exit;
	}

}
