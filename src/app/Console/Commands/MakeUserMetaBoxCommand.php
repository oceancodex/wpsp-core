<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeUserMetaBoxCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:user-meta-box
        {id? : The id of the user meta box}
        {--view : Create view files for this user meta box}';

	protected $description = 'Create a new user meta box. | Eg: php artisan make:user-meta-box custom_user_meta_box --view';

	protected $help = 'This command allows you to create a user meta box.';

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
		$id = $this->argument('id');

		// Nếu không khai báo, hãy hỏi.
		if (!$id) {
			$id = $this->ask('Please enter the ID of the user meta box (Eg: custom_user_meta_box)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($id)) {
				$this->error('Missing ID for the user meta box. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$createView = $this->confirm('Do you want to create view files for this user meta box?', false);
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($id, 'id');

		// Chuẩn bị thêm các biến để sử dụng.
		$createView = $createView ?? $this->option('view');

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/UserMetaBoxes/' . $id . '.php';
		$viewDir   = $mainPath . '/resources/views/user-meta-boxes/' . $id;

		if (File::exists($classPath) || File::exists($viewDir)) {
			$this->error('User meta box: "' . $id . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		if ($createView) {
			$content = File::get(__DIR__ . '/../Stubs/UserMetaBoxes/user-meta-box-view.stub');
		}
		else {
			$content = File::get(__DIR__ . '/../Stubs/UserMetaBoxes/user-meta-box.stub');
		}

		$content = str_replace(
			['{{ className }}', '{{ id }}'],
			[$id, $id],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($classPath));
		File::put($classPath, $content);

		/**
		 * ---
		 * Views.
		 * ---
		 */
		if ($createView) {
			$bladeExt    = class_exists('Illuminate\View\View') ? '.blade.php' : '.php';
			$nonBladeSep = class_exists('Illuminate\View\View') ? '' : '/non-blade';

			File::ensureDirectoryExists($viewDir);

			$views = [
				'main'       => 'main.view',
				'tab-1'      => 'tab-1.view',
				'tab-2'      => 'tab-2.view',
				'navigation' => 'navigation.view',
			];

			foreach ($views as $file => $stubFile) {
				$view = File::get(__DIR__ . '/../Views/UserMetaBoxes' . $nonBladeSep . '/' . $stubFile);

				$view = str_replace(
					['{{ className }}', '{{ id }}'],
					[$id, $id],
					$view
				);

				File::put("$viewDir/{$file}{$bladeExt}", $view);
			}
		}

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/UserMetaBoxes/user-meta-box.func');
		$func = str_replace(
			['{{ id }}'],
			[$id],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/UserMetaBoxes/user-meta-box.use');
		$use = str_replace(
			['{{ id }}'],
			[$id],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('UserMetaBoxes', 'user_meta_boxes', $func, $use);

		// Done.
		$this->info('Created new user meta box: "' . $id . '"');

		exit;
	}

}
