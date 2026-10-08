<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakePluginColumnCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:plugin-column
        {name? : The name of the plugin column.}
        {--view : Create a view file for this plugin column.}';

	protected $description = 'Create a new plugin column. | Eg: php artisan make:plugin-column custom_plugin_column --view';

	protected $help = 'This command allows you to create a custom column for plugin list table.';

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
			$name = $this->ask('Please enter the name of the plugin column (Eg: custom_plugin_column)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the plugin column. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$createView = $this->confirm('Do you want to create view files for this plugin column?', false);
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($name);

		// Chuẩn bị thêm các biến để sử dụng.
		$className  = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
		$createView = $createView ?? $this->option('view') ?: false;

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/PluginColumns/' . $className . '.php';
		$viewPath  = $mainPath . '/resources/views/plugin-columns/' . $name . '.blade.php';

		if (File::exists($classPath)) {
			$this->error('Media column: "' . $name . '" already exists! Please try again.');
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
			$view = File::get(__DIR__ . '/../Views/PluginColumns/plugin-column.view');
			$view = str_replace(
				['{{ name }}', '{{ class_name }}'],
				[$name, $className],
				$view
			);

			File::put($viewPath, $view);

			$stub = File::get(__DIR__ . '/../Stubs/PluginColumns/plugin-column-view.stub');
		}
		else {
			$stub = File::get(__DIR__ . '/../Stubs/PluginColumns/plugin-column.stub');
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
		$func = File::get(__DIR__ . '/../Funcs/PluginColumns/plugin-column.func');
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
		$use = File::get(__DIR__ . '/../Uses/PluginColumns/plugin-column.use');
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
		$this->addClassToRoute('PluginColumns', 'plugin_columns', $func, $use);

		// Done.
		$this->info('Created new plugin column: "' . $name . '"');

		exit;
	}

}
