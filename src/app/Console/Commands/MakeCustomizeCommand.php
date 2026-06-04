<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeCustomizeCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:customize
        {name? : The name of the customize.}';

	protected $description = 'Create a new customize. | Eg: php artisan make:customize custom_customize';

	protected $help = 'This command allows you to create a customize.';

	public function handle() {
		/**
		 * ---
		 * Funcs.
		 * ---
		 */
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		/**
		 * ---
		 * Khai báo, hỏi và kiểm tra.
		 * ---
		 */
		$name = $this->argument('name');

		// Nếu không khai báo, hãy hỏi.
		if (!$name) {
			$name = $this->ask('Please enter the name of the customize (Eg: custom_customize)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the customize. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
//			$createView = $this->confirm('Do you want to create view files for this customize?', false);
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($name);

		// Chuẩn bị thêm các biến để sử dụng.
		$className   = Str::slug($name, '_');
//		$createView  = $createView ?? $this->option('view');

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/Customizers/' . $className . '.php';
//		$viewPath  = $mainPath . '/resources/views/customizers/' . $name . '.blade.php';

		if (File::exists($classPath)) {
			$this->error('Customize: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class & Views.
		 * ---
		 */
//		if ($createView) {
//			$view = File::get(__DIR__ . '/../Views/Customizers/customize.view');
//			$view = str_replace(
//				['{{ name }}'],
//				[$name],
//				$view
//			);
//
//			File::ensureDirectoryExists(dirname($viewPath));
//			File::put($viewPath, $view);
//
//			$stub = File::get(__DIR__ . '/../Stubs/Customizers/customize-view.stub');
//		}
//		else {
			$stub = File::get(__DIR__ . '/../Stubs/Customizers/customize.stub');
//		}

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
		$func = File::get(__DIR__ . '/../Funcs/Customizers/customize.func');
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
		$use = File::get(__DIR__ . '/../Uses/Customizers/customize.use');
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
		$this->addClassToRoute('Customizers', 'customizers', $func, $use);


		// Done.
		$this->info('Created new customize: "' . $name . '"');

		exit;
	}

}
