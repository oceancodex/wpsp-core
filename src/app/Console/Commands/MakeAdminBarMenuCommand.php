<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeAdminBarMenuCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:admin-bar-menu
        {name? : The name of the admin bar menu.}
        {--parent= : The name of the parent admin bar menu.}';

	protected $description = 'Create a new admin bar menu. | Eg: php artisan make:admin-bar-menu custom_admin_bar_menu --parent=parent_admin_bar_menu';

	protected $help = 'This command allows you to create a admin bar menu.';

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
			$name = $this->ask('Please enter the name of the admin bar menu (Eg: custom_admin_bar_menu)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the admin bar menu. Please try again.');
				exit;
			}

			$parent = $this->ask('Please enter the name of the parent admin bar menu (optional)');
		}

		// Kiểm tra chuỗi hợp lệ.

		// Chuẩn bị thêm các biến để sử dụng.
		$className = Str::slug($name, '_');
		$parent    = $parent ?? $this->option('parent') ?: null;
		$parent    = $parent ? "'$parent'" : "''";

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/AdminBarMenus/' . $className . '.php';

		if (File::exists($classPath) || File::exists($classPath)) {
			$this->error('Admin bar menu: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/AdminBarMenus/admin-bar-menu.stub');
		$content = str_replace('{{ class_name }}', $className, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = str_replace('{{ parent }}', $parent, $content);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($classPath));
		File::put($classPath, $content);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/AdminBarMenus/admin-bar-menu.func');
		$func = str_replace(['{{ class_name }}', '{{ name }}'], [$className, $name], $func);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/AdminBarMenus/admin-bar-menu.use');
		$use = str_replace(['{{ class_name }}', '{{ name }}'], [$className, $name], $use);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('AdminBarMenus', 'admin_bar_menus', $func, $use);

		// Done.
		$this->info('Created new admin bar menu: "' . $name . '"');

		exit;
	}

}
