<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakePostTypeCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:post-type
        {name? : The name of the post type.}';

	protected $description = 'Create a new post type. | Eg: php artisan make:post-type custom_post_type';

	protected $help = 'This command allows you to create a post type...';

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
			$name = $this->ask('Please enter the name of the post type (Eg: event)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the post type. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($name);

		// Chuẩn bị thêm các biến để sử dụng.
		$className = Str::slug($name, '_');

		// Kiểm tra tồn tại.
		$path = $mainPath . '/app/WordPress/PostTypes/' . $className . '.php';

		if (File::exists($path)) {
			$this->error('Post type: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/PostTypes/posttype.stub');
		$content = str_replace(
			['{{ class_name }}', '{{ name }}'],
			[$className, $name],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/PostTypes/posttype.func');
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
		$use = File::get(__DIR__ . '/../Uses/PostTypes/posttype.use');
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
		$this->addClassToRoute('PostTypes', 'post_types', $func, $use);

		// Done.
		$this->info('Created new post type: "' . $name . '"');

		exit;
	}

}
