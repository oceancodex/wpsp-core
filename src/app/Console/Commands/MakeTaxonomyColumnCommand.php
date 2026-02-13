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

	protected $description = 'Create a new taxonomy column. | Eg: php artisan make:taxonomy-column custom_tax_column';

	protected $help = 'This command allows you to create a custom column for taxonomy list table.';

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
			$name = $this->ask('Please enter the name of the taxonomy column (Eg: custom_tax_column)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the taxonomy column. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($name);

		// Kiểm tra tồn tại.
		$path = $mainPath . '/app/WordPress/TaxonomyColumns/' . $name . '.php';

		if (File::exists($path)) {
			$this->error('Taxonomy column: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$stub = File::get(__DIR__ . '/../Stubs/TaxonomyColumns/taxonomy_column.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $stub);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/TaxonomyColumns/taxonomy_column.func');
		$func = str_replace(['{{ name }}'], [$name], $func);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/TaxonomyColumns/taxonomy_column.use');
		$use = str_replace(['{{ name }}'], [$name], $use);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('TaxonomyColumns', 'taxonomy_columns', $func, $use);

		// Done.
		$this->info('Created new taxonomy column: "' . $name . '"');

		exit;
	}

}
