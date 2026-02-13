<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeAdminPageCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:admin-page
        {path? : The path of the admin page}
        {--view : Create view files for this admin page}';

	protected $description = 'Create a new admin page. | Eg: php artisan make:admin-page custom-admin-page --view';

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
		$path = $this->argument('path');

		// Nếu không khai báo, hãy hỏi.
		if (!$path) {
			$path = $this->ask('Please enter the path of the admin page (Eg: custom-admin-page)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($path)) {
				$this->error('Missing path for the admin page. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$createView = $this->confirm('Do you want to create view files for this admin page?', false);
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($path, 'path');

		// Chuẩn bị thêm các biến để sử dụng.
		$name       = Str::slug(str_replace('-', '_', $path), '_');
		$createView = $createView ?? $this->option('view') ?: false;

		// Không cần validate "name", vì command này yêu cầu "path" mà path có thể chứa "-".
		// $name sẽ được slugify từ "path" ra.

		// Kiểm tra tồn tại.
		$adminClassPath = $mainPath . '/app/WordPress/AdminPages/' . $name . '.php';
		$viewDirPath    = $mainPath . '/resources/views/admin-pages/' . $path;

		if (File::exists($adminClassPath) || File::exists($viewDirPath)) {
			$this->error('Admin page: "' . $path . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		if ($createView) {
			$content = File::get(__DIR__ . '/../Stubs/AdminPages/adminpage-view.stub');
		}
		else {
			$content = File::get(__DIR__ . '/../Stubs/AdminPages/adminpage.stub');
		}

		$content = str_replace(
			['{{ className }}', '{{ name }}', '{{ path }}'],
			[$name, $name, $path],
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
		if ($createView) {
			$bladeExt    = class_exists('Illuminate\View\View') ? '.blade.php' : '.php';
			$nonBladeSep = class_exists('Illuminate\View\View') ? '' : '/non-blade';

			File::ensureDirectoryExists($viewDirPath);

			$viewFiles = [
				'main'       => 'adminpage.view',
				'dashboard'  => 'dashboard.view',
				'tab-1'      => 'tab-1.view',
				'navigation' => 'navigation.view',
			];

			foreach ($viewFiles as $filename => $stub) {
				$view = File::get(__DIR__ . '/../Views/AdminPages' . $nonBladeSep . '/' . $stub);

				$view = str_replace(
					['{{ name }}', '{{ path }}'],
					[$name, $path],
					$view
				);

				File::put($viewDirPath . "/{$filename}{$bladeExt}", $view);
			}
		}

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/AdminPages/adminpage.func');
		$func = str_replace(['{{ name }}', '{{ path }}'],
			[$name, $path],
			$func);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/AdminPages/adminpage.use');
		$use = str_replace(
			['{{ name }}', '{{ path }}'],
			[$name, $path],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('AdminPages', 'admin_pages', $func, $use);

		// Done.
		$this->info("Created new admin page: {$path}");

		exit;
	}

}
