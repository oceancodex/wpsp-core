<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeListTableCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:list-table
        {name? : The name of the list table.}';

	protected $description = 'Create a new list table. | Eg: php artisan make:list-table MyListTable';

	protected $help = 'This command allows you to create a list table.';

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
			$name = $this->ask('Please enter the name of the list table (Eg: MyListTable)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($name)) {
				$this->error('Missing name for the list table. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($name);

		// Kiểm tra tồn tại.
		$path = $mainPath . '/app/WordPress/ListTables/' . $name . '.php';

		if (File::exists($path)) {
			$this->error('List table: "' . $name . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/ListTables/listtable.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		// Done.
		$this->info('Created new list table: "' . $name . '"');

		exit;
	}

}
