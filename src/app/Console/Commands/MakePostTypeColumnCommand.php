<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakePostTypeColumnCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:post-type-column
        {name? : The name of the post type column.}';

	protected $description = 'Create a new post type column. | Eg: php artisan make:post-type-column my_custom_column';

	protected $help = 'This command allows you to create a custom column for post type list table.';

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
			$name = $this->ask('Please enter the name of the post type column (Eg: my_custom_column)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the post type column. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($name);

		// Kiểm tra tồn tại.
		$path = $mainPath . '/app/WordPress/PostTypeColumns/' . $name . '.php';

		if (File::exists($path)) {
			$this->error('Post type column: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$stub = File::get(__DIR__ . '/../Stubs/PostTypeColumns/post_type_column.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $stub);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/PostTypeColumns/post_type_column.func');
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
		$use = File::get(__DIR__ . '/../Uses/PostTypeColumns/post_type_column.use');
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
		$this->addClassToRoute('PostTypeColumns', 'post_type_columns', $func, $use);

		// Done.
		$this->info('Created new post type column: "' . $name . '"');

		exit;
	}

}
