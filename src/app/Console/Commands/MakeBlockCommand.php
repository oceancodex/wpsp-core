<?php

namespace WPSPCORE\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeBlockCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:block
        {name? : The block name}';

	protected $description = 'Create a new block. | Eg: php artisan make:block custom-block';

	public function handle() {
		/**
		 * ---
		 * Funcs.
		 * ---
		 */
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;
		$textDomain  = $this->funcs->_getTextDomain();

		/**
		 * ---
		 * Khai báo, hỏi và kiểm tra.
		 * ---
		 */
		$name = $this->argument('name');

		// Nếu không khai báo, hãy hỏi.
		if (!$name) {
			$name = $this->ask('Please enter the block name (Eg: custom-block)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing block name. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($name, 'name');

		// Chuẩn bị thêm các biến để sử dụng.
		$className = str_replace('-', '_', $name);
		$className = Str::slug($className, '_');

		// Kiểm tra tồn tại.
		$adminClassPath = $mainPath . '/app/WordPress/Blocks/' . $className . '.php';
		$viewDirPath    = $mainPath . '/resources/views/blocks/src/' . $name;

		if (File::exists($adminClassPath) || File::exists($viewDirPath)) {
			$this->error('The block "' . $name . '" already exists!');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/Blocks/block.stub');
		$content = str_replace(
			['{{ name }}', '{{ className }}'],
			[$name, $className],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($adminClassPath));
		File::put($adminClassPath, $content);

		/**
		 * ---
		 * Views.
		 * ---
		 */
		File::ensureDirectoryExists($viewDirPath);

		$viewFiles = [
			'block.json',
			'edit.js',
			'editor.scss',
			'index.js',
			'render.php',
			'script.js',
			'style.scss',
			'view.js',
		];

		foreach ($viewFiles as $viewFile) {
			$view = File::get(__DIR__ . '/../Stubs/Blocks/' . $viewFile);

			$view = str_replace(
				['{{ name }}', '{{ className }}', '{{ textDomain }}'],
				[$name, $className, $textDomain],
				$view
			);

			$view = $this->replaceNamespaces($view);

			File::put($viewDirPath . "/{$viewFile}", $view);
		}

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/Blocks/block.func');
		$func = str_replace(
			['{{ name }}', '{{ className }}'],
			[$name, $className],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/Blocks/block.use');
		$use = str_replace(
			['{{ name }}', '{{ className }}'],
			[$name, $className],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('Blocks', 'blocks', $func, $use);

		$this->warn('The block "' . $name . '" is currently being built...');
		$this->newLine();

		/**
		 * ---
		 * Build.
		 * ---
		 */
		exec('npm run blocks-build');

		// Done.
		$this->info("Created new block: {$name}");

		exit;
	}

}
