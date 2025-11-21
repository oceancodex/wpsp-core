<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeTemplateCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:template
        {name? : The name of the template.}';

	protected $description = 'Create a new page template.               | Eg: bin/wpsp make:template custom_template';

	protected $help = 'This command allows you to create a page template.';

	public function handle(): void {
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		/* -------------------------------------------------
		 *  Ask interactive
		 * ------------------------------------------------- */
		if (!$name) {
			$name = $this->ask('Please enter the name of the template');

			if (empty($name)) {
				$this->error('Missing name for the template. Please try again.');
				exit;
			}
		}

		$nameSlugify = Str::slug($name, '_');

		/* -------------------------------------------------
		 *  Check exists
		 * ------------------------------------------------- */
		$classPath = $mainPath . '/app/Components/Templates/' . $nameSlugify . '.php';
		$viewPath  = $mainPath . '/resources/views/modules/templates/' . $name . '.php';

		if (File::exists($classPath)) {
			$this->error('[ERROR] Template: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/* -------------------------------------------------
		 *  CREATE CLASS FILE
		 * ------------------------------------------------- */
		$content = File::get(__DIR__ . '/../Stubs/Templates/template.stub');
		$content = str_replace(
			['{{ className }}', '{{ name }}', '{{ name_slugify }}'],
			[$nameSlugify, $name, $nameSlugify],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($classPath));
		File::put($classPath, $content);

		/* -------------------------------------------------
		 *  CREATE VIEW FILE
		 * ------------------------------------------------- */
		$view = File::get(__DIR__ . '/../Views/Templates/template.view');
		$view = str_replace(
			['{{ name }}', '{{ name_slugify }}'],
			[$name, $nameSlugify],
			$view
		);

		File::ensureDirectoryExists(dirname($viewPath));
		File::put($viewPath, $view);

		/* -------------------------------------------------
		 *  REGISTER into ROUTE (func + use)
		 * ------------------------------------------------- */
		$func = File::get(__DIR__ . '/../Funcs/Templates/template.func');
		$func = str_replace(
			['{{ name }}', '{{ name_slugify }}'],
			[$name, $nameSlugify],
			$func
		);

		$use = File::get(__DIR__ . '/../Uses/Templates/template.use');
		$use = str_replace(
			['{{ name }}', '{{ name_slugify }}'],
			[$name, $nameSlugify],
			$use
		);
		$use = $this->replaceNamespaces($use);

		$this->addClassToRoute('Templates', 'templates', $func, $use);

		/* -------------------------------------------------
		 *  DONE
		 * ------------------------------------------------- */
		$this->info('Created new page template: "' . $name . '"');

		exit;
	}

}
