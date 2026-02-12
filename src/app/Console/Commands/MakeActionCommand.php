<?php

namespace WPSPCORE\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeActionCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:action
        {action? : The action hook}';

	protected $description = 'Create a new action hook. | Eg: php artisan make:action wp_head';

	public function handle() {
		/**
		 * ---
		 * Funcs.
		 * ---
		 */
		$this->funcs = $this->getLaravel()->make('funcs');

		/**
		 * ---
		 * Khai báo, hỏi và kiểm tra.
		 * ---
		 */
		$action = $this->argument('action');

		// Nếu không khai báo, hãy hỏi.
		if (!$action) {
			$action = $this->ask('Please enter the action hook (Eg: wp_head)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($action)) {
				$this->error('Missing action hook. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($action, 'action');

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/Actions/action.func');
		$func = str_replace(
			['{{ action }}'],
			[$action],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/Actions/action.use');
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('Actions', 'actions', $func, $use);

		// Done.
		$this->info("Created new action hook: {$action}");

		exit;
	}

}
