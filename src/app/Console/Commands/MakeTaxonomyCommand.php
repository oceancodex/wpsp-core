<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeTaxonomyCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:taxonomy
        {name? : The name of the taxonomy.}';

	protected $description = 'Create a new taxonomy. | Eg: bin/wpsp make:taxonomy custom_taxonomy';

	protected $help = 'This command allows you to create a taxonomy...';

	public function handle() {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		// Interactive ask
		if (!$name) {
			$name = $this->ask('Please enter the name of the taxonomy');

			if (empty($name)) {
				$this->error('Missing name for the taxonomy. Please try again.');
				exit;
			}
		}

		// Validate
		$this->validateClassName($name);

		// Path
		$path = $mainPath . '/app/WordPress/Taxonomies/' . $name . '.php';

		// Check exists
		if (File::exists($path)) {
			$this->error('Taxonomy: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/** -------------------------------------------------
		 *  CREATE CLASS FILE
		 * ------------------------------------------------- */
		$content = File::get(__DIR__ . '/../Stubs/Taxonomies/taxonomy.stub');
		$content = str_replace(
			['{{ className }}', '{{ name }}'],
			[$name, $name],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		/** -------------------------------------------------
		 *  ADD TO ROUTE (func + use)
		 * ------------------------------------------------- */
		$func = File::get(__DIR__ . '/../Funcs/Taxonomies/taxonomy.func');
		$func = str_replace(
			['{{ name }}'],
			[$name],
			$func
		);

		$use = File::get(__DIR__ . '/../Uses/Taxonomies/taxonomy.use');
		$use = str_replace(
			['{{ name }}'],
			[$name],
			$use
		);
		$use = $this->replaceNamespaces($use);

		$this->addClassToRoute('Taxonomies', 'taxonomies', $func, $use);

		/** -------------------------------------------------
		 *  DONE
		 * ------------------------------------------------- */
		$this->info('Created new taxonomy: "' . $name . '"');

		exit;
	}

}
