<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakePostTypeColumnCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:post-type-column
        {name? : The name of the post type column.}
        {--view : Create a view file for this post type column.}';

	protected $description = 'Create a new post type column. | Eg: php artisan make:post-type-column my_custom_column --view';

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

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$createView = $this->confirm('Do you want to create view files for this post type column?', false);
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($name);

		// Chuẩn bị thêm các biến để sử dụng.
		$className  = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
		$createView = $createView ?? $this->option('view') ?: false;

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/PostTypeColumns/' . $className . '.php';
		$viewPath  = $mainPath . '/resources/views/post-type-columns/' . $name . '.blade.php';

		if (File::exists($classPath)) {
			$this->error('Post type column: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		if ($createView) {
			File::ensureDirectoryExists(dirname($viewPath));

			$view = File::get(__DIR__ . '/../Views/PostTypeColumns/post-type-column.view');
			$view = str_replace(
				['{{ name }}', '{{ class_name }}'],
				[$name, $className],
				$view
			);

			File::put($viewPath, $view);

			$stub = File::get(__DIR__ . '/../Stubs/PostTypeColumns/post-type-column-view.stub');
		}
		else {
			$stub = File::get(__DIR__ . '/../Stubs/PostTypeColumns/post-type-column.stub');
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
		$func = File::get(__DIR__ . '/../Funcs/PostTypeColumns/post-type-column.func');
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
		$use = File::get(__DIR__ . '/../Uses/PostTypeColumns/post-type-column.use');
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
		$this->addClassToRoute('PostTypeColumns', 'post_type_columns', $func, $use);

		// Done.
		$this->info('Created new post type column: "' . $name . '"');

		exit;
	}

}
