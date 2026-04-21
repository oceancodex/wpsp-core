<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeAdminBarMenuCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:admin-bar-menu
        {name? : The name of the admin bar menu.}';

	protected $description = 'Create a new admin bar menu. | Eg: php artisan make:admin-bar-menu custom_admin_bar_menu';

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
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($name);

		// Chuẩn bị thêm các biến để sử dụng.
		$path = $mainPath . '/app/WordPress/AdminBarMenus/' . $name . '.php';

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/AdminBarMenus/adminbarmenu.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/AdminBarMenus/adminbarmenu.func');
		$func = str_replace(['{{ name }}'], [$name], $func);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/AdminBarMenus/adminbarmenu.use');
		$use = str_replace(['{{ name }}'], [$name], $use);
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
