<?php

namespace WPSPCORE\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeFilterCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:filter
        {filter? : The filter hook}';

	protected $description = 'Create a new filter hook. | Eg: php artisan make:filter the_content';

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
		$filter = $this->argument('filter');

		// Nếu không khai báo, hãy hỏi.
		if (!$filter) {
			$filter = $this->ask('Please enter the filter hook (Eg: the_content)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($filter)) {
				$this->error('Missing filter hook. Please try again.');
				exit;
			}
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($filter, 'filter');

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/Filters/filter.func');
		$func = str_replace(
			['{{ filter }}'],
			[$filter],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/Filters/filter.use');
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('Filters', 'filters', $func, $use);

		// Done.
		$this->info("Created new filter hook: {$filter}");

		exit;
	}

}
