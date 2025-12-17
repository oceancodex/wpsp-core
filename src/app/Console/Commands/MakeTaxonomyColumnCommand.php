<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeTaxonomyColumnCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:taxonomy-column
        {name? : The name of the taxonomy column.}';

	protected $description = 'Create a new taxonomy column.             | Eg: bin/wpsp make:taxonomy-column my_custom_column';

	protected $help = 'This command allows you to create a custom column for taxonomy list table.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		$name = $this->argument('name');

		// Ask interactively
		if (!$name) {
			$name = $this->ask('Please enter the name of the taxonomy column');

			if (empty($name)) {
				$this->error('Missing name for the taxonomy column. Please try again.');
				exit;
			}
		}

		$nameSlugify = Str::slug($name, '_');

		// Validate class name
		$this->validateClassName($name);

		// Path
		$path = $mainPath . '/app/WordPress/TaxonomyColumns/' . $name . '.php';

		// Check exists
		if (File::exists($path)) {
			$this->error('[ERROR] Taxonomy column: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/* -------------------------------------------------
		 * Create class file
		 * ------------------------------------------------- */
		$stub = File::get(__DIR__ . '/../Stubs/TaxonomyColumns/taxonomy_column.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $stub);

		/* -------------------------------------------------
		 * Register route entry
		 * ------------------------------------------------- */
		$func = File::get(__DIR__ . '/../Funcs/TaxonomyColumns/taxonomy_column.func');
		$func = str_replace(['{{ name }}', '{{ name_slugify }}'], [$name, $nameSlugify], $func);

		$use = File::get(__DIR__ . '/../Uses/TaxonomyColumns/taxonomy_column.use');
		$use = str_replace(['{{ name }}', '{{ name_slugify }}'], [$name, $nameSlugify], $use);
		$use = $this->replaceNamespaces($use);

		$this->addClassToRoute('TaxonomyColumns', 'taxonomy_columns', $func, $use);

		/* -------------------------------------------------
		 * Done
		 * ------------------------------------------------- */
		$this->info('Created new taxonomy column: "' . $name . '"');

		exit;
	}

}
