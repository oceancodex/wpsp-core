<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeFrontPageCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:front-page
        {path? : The path of the front page.}
        {--method= : The method for front page.}
        {--view : Create a view file for this front page}';

	protected $description = 'Create a new front page. | Eg: php artisan make:front-page my-front-page --method=GET --view';

	protected $help = 'This command allows you to create a front page.';

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
		$path = $this->argument('path');

		// Nếu không khai báo, hãy hỏi.
		if (!$path) {
			$path = $this->ask('Please enter the path of the front page (Eg: my-front-page)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($path)) {
				$this->error('Missing path for the front page. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy hỏi tiếp.
			$method     = $this->ask('Please enter the HTTP method for the front page', 'GET');
			$createView = $this->confirm('Do you want to create view files for this front page?', false);
		}
		else {
			$method     = $this->option('method');
			$createView = $this->option('view');
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($path, 'path');

		// Chuẩn bị thêm các biến để sử dụng.
		$className = Str::slug($path, '_');
		$method    = strtolower($method ?: 'GET');

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/FrontPages/' . $className . '.php';
		$viewPath  = $mainPath . '/resources/views/front-pages/' . $path . '.blade.php';

		if (File::exists($classPath) || File::exists($viewPath)) {
			$this->error('Front page: "' . $path . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		if ($createView) {
			File::ensureDirectoryExists(dirname($viewPath));

			$view = File::get(__DIR__ . '/../Views/FrontPages/front-page.view');
			$view = str_replace(
				['{{ class_name }}', '{{ path }}', '{{ method }}'],
				[$className, $path, $method],
				$view
			);

			File::put($viewPath, $view);

			$stub = File::get(__DIR__ . '/../Stubs/FrontPages/front-page-view.stub');
		}
		else {
			$stub = File::get(__DIR__ . '/../Stubs/FrontPages/front-page.stub');
		}

		$stub = str_replace(
			['{{ class_name }}', '{{ path }}', '{{ method }}'],
			[$className, $path, $method],
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
		$func = File::get(__DIR__ . '/../Funcs/FrontPages/front-page.func');
		$func = str_replace(
			['{{ class_name }}', '{{ path }}', '{{ method }}'],
			[$className, $path, $method],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/FrontPages/front-page.use');
		$use = str_replace(
			['{{ class_name }}', '{{ path }}', '{{ method }}'],
			[$className, $path, $method],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('FrontPages', 'front_pages', $func, $use);

		// Done.
		$this->info('Created new front page: "' . $path . '"');

		exit;
	}

}