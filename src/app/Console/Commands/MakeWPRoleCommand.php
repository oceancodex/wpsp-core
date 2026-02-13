<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeWPRoleCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:wp-role
        {name? : The name of the role.}';

	protected $description = 'Create a new role. | Eg: php artisan make:wp-role custom_role';

	protected $help = 'This command allows you to create a role...';

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
			$name = $this->ask('Please enter the name of the role (Eg: custom_role)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the role. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($name);

		// Kiểm tra tồn tại.
		$path = $mainPath . '/app/WordPress/WPRoles/' . $name . '.php';

		if (File::exists($path)) {
			$this->error('Role: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/WPRoles/wprole.stub');
		$content = str_replace(
			['{{ className }}', '{{ name }}'],
			[$name, $name],
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
		$func = File::get(__DIR__ . '/../Funcs/WPRoles/wprole.func');
		$func = str_replace(
			['{{ className }}', '{{ name }}'],
			[$name, $name],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/WPRoles/wprole.use');
		$use = str_replace(
			['{{ className }}', '{{ name }}'],
			[$name, $name],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('WPRoles', 'wp_roles', $func, $use);

		// Done.
		$this->info('Created new WP role: "' . $name . '"');

		exit;
	}

}
