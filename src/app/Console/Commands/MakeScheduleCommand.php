<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeScheduleCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:schedule
        {hook? : The hook of the schedule.}
        {--type= : The type of the schedule.}
        {interval? : The interval of the schedule.}';

	protected $description = 'Create a new schedule. | Eg: php artisan make:schedule custom_schedule_hook hourly';

	protected $help = 'This command allows you to create a schedule.';

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
		$hook = $this->argument('hook');

		// Nếu không khai báo, hãy hỏi.
		if (!$hook) {
			$hook = $this->ask('Please enter the hook of the schedule (Eg: custom_schedule_hook)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($hook)) {
				$this->error('Missing hook for the schedule. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$type     = $this->ask('Please enter the type of the schedule (Eg: wpsp, wordpress)', 'wordpress');
			$interval = $this->ask('Please enter the interval of the schedule (everyMinute, hourly, daily, weekly, monthly, yearly,...)', 'everyMinute');
		}

		// Chuẩn bị thêm các biến để sử dụng.
		$type     = $type ?? $this->option('type') ?: 'wordpress';
		$interval = $interval ?? $this->argument('interval') ?: 'everyMinute';

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($hook, 'hook');
		$this->validateClassName($interval, 'interval');

		// Kiểm tra tồn tại.
		$path = $mainPath . '/app/WordPress/Schedules/' . $hook . '.php';

		if (File::exists($path)) {
			$this->error('Schedule: "' . $hook . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$content = File::get(__DIR__ . '/../Stubs/Schedules/schedule.stub');
		$content = str_replace(
			['{{ className }}', '{{ hook }}', '{{ interval }}'],
			[$hook, $hook, $interval],
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
		$func = File::get(__DIR__ . '/../Funcs/Schedules/schedule'.($type == 'wpsp' ? '-wpsp' : '').'.func');
		$func = str_replace(
			['{{ hook }}', '{{ interval }}'],
			[$hook, $interval],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/Schedules/schedule.use');
		$use = str_replace(
			['{{ hook }}', '{{ interval }}'],
			[$hook, $interval],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('Schedules', 'schedules', $func, $use);

		// Done.
		$this->info('Created new schedule: "' . $hook . '"');

		exit;
	}

}
