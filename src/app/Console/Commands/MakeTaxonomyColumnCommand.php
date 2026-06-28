<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeTaxonomyColumnCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:taxonomy-column
        {name? : The name of the taxonomy column.}
        {--view : Create a view file for this taxonomy column.}';

	protected $description = 'Create a new taxonomy column. | Eg: php artisan make:taxonomy-column custom_tax_column --view';

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

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$createView = $this->confirm('Do you want to create view files for this taxonomy column?', false);
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($name);

		// Chuẩn bị thêm các biến để sử dụng.
		$className = Str::slug($name, '_');
		$createView = $createView ?? $this->option('view') ?: false;

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/TaxonomyColumns/' . $className . '.php';
		$viewPath  = $mainPath . '/resources/views/taxonomy-columns/' . $name . '.blade.php';

		if (File::exists($classPath)) {
			$this->error('Taxonomy column: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		if ($createView) {
			File::ensureDirectoryExists(dirname($viewPath));

			/**
			 * ---
			 * Create view files.
			 */
			$view = File::get(__DIR__ . '/../Views/TaxonomyColumns/taxonomy-column.view');
			$view = str_replace(
				['{{ name }}', '{{ class_name }}'],
				[$name, $className],
				$view
			);

			File::put($viewPath, $view);

			$stub = File::get(__DIR__ . '/../Stubs/TaxonomyColumns/taxonomy-column-view.stub');
		}
		else {
			$stub = File::get(__DIR__ . '/../Stubs/TaxonomyColumns/taxonomy-column.stub');
		}

		$stub = str_replace(
			['{{ class_name }}', '{{ name }}'],
			[$className, $name],
			$stub
		);
		$stub = $this->replaceNamespaces($stub);

		File::ensureDirectoryExists(dirname($classPath));
		File::put($classPath, $stub);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/TaxonomyColumns/taxonomy-column.func');
		$func = str_replace(
			['{{ class_name }}', '{{ name }}'],
			[$className, $name],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/TaxonomyColumns/taxonomy-column.use');
		$use = str_replace(
			['{{ class_name }}', '{{ name }}'],
			[$className, $name],
			$use
		);
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
