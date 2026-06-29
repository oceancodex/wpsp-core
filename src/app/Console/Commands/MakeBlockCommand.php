<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeBlockCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:block
        {name? : The block name}
        {--view : Use Blade template engine for render this block?}';

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
		$appShortName = $this->funcs->_getAppShortName();

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

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$createView = $this->confirm('Do you want to use Blade template engine for render this block?', false);
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($name, 'name');

		// Chuẩn bị thêm các biến để sử dụng.
		$className    = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
		$blockDirName = Str::slug($name, '-');
		$createView   = $createView ?? $this->option('view') ?: false;
		$isBlade      = $createView ? '.blade' : null;

		// Kiểm tra tồn tại.
		$classPath   = $mainPath . '/app/WordPress/Blocks/' . $className . '.php';
		$viewDirPath = $mainPath . '/resources/views/blocks/src/' . $blockDirName;

		if (File::exists($classPath) || File::exists($viewDirPath)) {
			$this->error('The block "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$stub = File::get(__DIR__ . '/../Stubs/Blocks/block' . ($createView ? '-view' : '') . '.stub');
		$stub = str_replace(
			['{{ name }}', '{{ class_name }}', '{{ text_domain }}', '{{ block_dir_name }}', '{{ app_short_name }}', '{{ is_blade }}'],
			[$name, $className, $textDomain, $blockDirName, $appShortName, $isBlade],
			$stub
		);
		$stub = $this->replaceNamespaces($stub);

		File::ensureDirectoryExists(dirname($classPath));
		File::put($classPath, $stub);

		/**
		 * ---
		 * Views.
		 * ---
		 */
		File::ensureDirectoryExists($viewDirPath);

		$viewFiles = [
			'block.json',
			'edit.js',
			'save.js',
			'editor.scss',
			'index.js',
			'render.php',
			'render.blade.php',
			'script.js',
			'style.scss',
			'view.js',
		];

		foreach ($viewFiles as $viewFile) {
			$view = File::get(__DIR__ . '/../Stubs/Blocks/' . $viewFile);

			$view = str_replace(
				['{{ name }}', '{{ class_name }}', '{{ text_domain }}', '{{ block_dir_name }}', '{{ app_short_name }}', '{{ is_blade }}'],
				[$name, $className, $textDomain, $blockDirName, $appShortName, $isBlade],
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
			['{{ name }}', '{{ class_name }}', '{{ text_domain }}', '{{ block_dir_name }}', '{{ app_short_name }}', '{{ is_blade }}'],
			[$name, $className, $textDomain, $blockDirName, $appShortName, $isBlade],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/Blocks/block.use');
		$use = str_replace(
			['{{ name }}', '{{ class_name }}', '{{ text_domain }}', '{{ block_dir_name }}', '{{ app_short_name }}', '{{ is_blade }}'],
			[$name, $className, $textDomain, $blockDirName, $appShortName, $isBlade],
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
//		$this->newLine();

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
