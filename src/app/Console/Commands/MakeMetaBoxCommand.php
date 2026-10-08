<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeMetaBoxCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:meta-box
        {id? : The ID of the meta box.}
        {--view : Create a view file for this meta box}';

	protected $description = 'Create a new meta box. | Eg: php artisan make:meta-box custom_meta_box --view';

	protected $help = 'This command allows you to create a meta box.';

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
		$id = $this->argument('id');

		// Nếu không khai báo, hãy hỏi.
		if (!$id) {
			$id = $this->ask('Please enter the ID of the meta box (Eg: custom_meta_box)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($id)) {
				$this->error('Missing ID for the meta box. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$createView = $this->confirm('Do you want to create view files for this meta box?', false);
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($id, 'id');

		// Chuẩn bị thêm các biến để sử dụng.
		$className  = preg_replace('/[^A-Za-z0-9_]/', '_', $id);
		$createView = $createView ?? $this->option('view') ?: false;

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/MetaBoxes/' . $className . '.php';
		$viewPath  = $mainPath . '/resources/views/meta-boxes/' . $id . '.blade.php';

		if (File::exists($classPath)) {
			$this->error('Meta box: "' . $id . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		if ($createView) {
			File::ensureDirectoryExists(dirname($viewPath));

			$view = File::get(__DIR__ . '/../Views/MetaBoxes/meta-box.view');
			$view = str_replace(
				['{{ id }}', '{{ class_name }}'],
				[$id, $className],
				$view
			);

			File::put($viewPath, $view);

			$stub = File::get(__DIR__ . '/../Stubs/MetaBoxes/meta-box-view.stub');
		}
		else {
			$stub = File::get(__DIR__ . '/../Stubs/MetaBoxes/meta-box.stub');
		}

		$stub = str_replace(
			['{{ id }}', '{{ class_name }}'],
			[$id, $className],
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
		$func = File::get(__DIR__ . '/../Funcs/MetaBoxes/meta-box.func');
		$func = str_replace(
			['{{ id }}', '{{ class_name }}'],
			[$id, $className],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/MetaBoxes/meta-box.use');
		$use = str_replace(
			['{{ id }}', '{{ class_name }}'],
			[$id, $className],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('MetaBoxes', 'meta_boxes', $func, $use);

		// Done.
		$this->info('Created new meta box: "' . $id . '"');

		exit;
	}

}
