<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeShortcodeCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:shortcode
        {name? : The name of the shortcode.}
        {--view : Create view files for this shortcode}';

	protected $description = 'Create a new shortcode. | Eg: php artisan make:shortcode custom_shortcode --view';

	protected $help = 'This command allows you to create a shortcode.';

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
			$name = $this->ask('Please enter the name of the shortcode (Eg: custom_shortcode)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the shortcode. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$createView = $this->confirm('Do you want to create view files for this shortcode?', false);
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($name);

		// Chuẩn bị thêm các biến để sử dụng.
		$createView  = $createView ?? $this->option('view');

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/Shortcodes/' . $name . '.php';
		$viewPath  = $mainPath . '/resources/views/shortcodes/' . $name . '.blade.php';

		if (File::exists($classPath)) {
			$this->error('Shortcode: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class & Views.
		 * ---
		 */
		if ($createView) {
			$view = File::get(__DIR__ . '/../Views/Shortcodes/shortcode.view');
			$view = str_replace(
				['{{ name }}'],
				[$name],
				$view
			);

			File::ensureDirectoryExists(dirname($viewPath));
			File::put($viewPath, $view);

			$stub = File::get(__DIR__ . '/../Stubs/Shortcodes/shortcode-view.stub');
		}
		else {
			$stub = File::get(__DIR__ . '/../Stubs/Shortcodes/shortcode.stub');
		}

		$stub = str_replace(
			['{{ className }}', '{{ name }}'],
			[$name, $name],
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
		$func = File::get(__DIR__ . '/../Funcs/Shortcodes/shortcode.func');
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
		$use = File::get(__DIR__ . '/../Uses/Shortcodes/shortcode.use');
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
		$this->addClassToRoute('Shortcodes', 'shortcodes', $func, $use);


		// Done.
		$this->info('Created new shortcode: "' . $name . '"');

		exit;
	}

}
