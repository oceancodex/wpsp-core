<?php

namespace WPSPCORE\app\Console\Commands;

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

	protected $description = 'Create a new front page. | Eg: php artisan make:front-page my-front-page --method=GET';

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
			$method = $this->ask('Please enter the HTTP method for the front page', 'GET');
			$createView = $this->confirm('Do you want to create view files for this meta box?', false);
		}
		else {
			$method = $this->option('method');
			$createView = $this->option('view');
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($path, 'path');

		// Chuẩn bị thêm các biến để sử dụng.
		$name = Str::slug($path, '_');
		$method = strtolower($method ?: 'GET');

		// Kiểm tra tồn tại.
		$componentPath = $mainPath . '/app/WordPress/FrontPages/' . $name . '.php';
		$viewPath      = $mainPath . '/resources/views/front-pages/' . $path . '.blade.php';

		if (File::exists($componentPath) || File::exists($viewPath)) {
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

			$view = File::get(__DIR__ . '/../Views/FrontPages/frontpage.view');
			$view = str_replace(
				['{{ name }}', '{{ path }}', '{{ method }}'],
				[$name, $path, $method],
				$view
			);

			File::put($viewPath, $view);

			$content = File::get(__DIR__ . '/../Stubs/FrontPages/frontpage-view.stub');
		}
		else {
			$content = File::get(__DIR__ . '/../Stubs/FrontPages/frontpage.stub');
		}

		$content = str_replace(
			['{{ className }}', '{{ name }}', '{{ path }}', '{{ method }}'],
			[$name, $name, $path, $method],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($componentPath));
		File::put($componentPath, $content);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/FrontPages/frontpage.func');
		$func = str_replace(
			['{{ name }}', '{{ path }}', '{{ method }}'],
			[$name, $path, $method],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/FrontPages/frontpage.use');
		$use = str_replace(
			['{{ name }}', '{{ path }}', '{{ method }}'],
			[$name, $path, $method],
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