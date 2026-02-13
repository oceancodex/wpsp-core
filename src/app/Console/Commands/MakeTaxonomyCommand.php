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

	protected $description = 'Create a new taxonomy. | Eg: php artisan make:taxonomy custom_taxonomy';

	protected $help = 'This command allows you to create a taxonomy...';

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
			$name = $this->ask('Please enter the name of the taxonomy (Eg: custom_taxonomy)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the taxonomy. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($name);

		// Kiểm tra tồn tại.
		$path = $mainPath . '/app/WordPress/Taxonomies/' . $name . '.php';

		if (File::exists($path)) {
			$this->error('Taxonomy: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/Taxonomies/taxonomy.stub');
		$content = str_replace(
			['{{ className }}', '{{ name }}'],
			[$name, $name],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/Taxonomies/taxonomy.func');
		$func = str_replace(
			['{{ name }}'],
			[$name],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/Taxonomies/taxonomy.use');
		$use = str_replace(
			['{{ name }}'],
			[$name],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('Taxonomies', 'taxonomies', $func, $use);

		// Done.
		$this->info('Created new taxonomy: "' . $name . '"');

		exit;
	}

}
