<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeScheduleCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:schedule
        {hook? : The hook name of the schedule.}
        {--type= : The type of the schedule (wpsp | wordpress).}
        {interval? : The interval of the schedule.}';

	protected $description = 'Create a new schedule. | Eg: php artisan make:schedule custom_schedule_hook --type=wordpress hourly';

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
			$type     = $this->ask('Please enter the type of the schedule (wpsp | wordpress)', 'wordpress');
			$interval = $this->ask('Please enter the interval of the schedule (everyMinute, hourly, daily, weekly, monthly, yearly,...)', 'everyMinute');
		}

		// Chuẩn bị thêm các biến để sử dụng.
		$className = Str::slug($hook, '_');
		$type      = $type ?? $this->option('type') ?: 'wordpress';
		$interval  = $interval ?? $this->argument('interval') ?: 'everyMinute';

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($hook, 'hook');
		$this->validateClassName($interval, 'interval');

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/Schedules/' . $className . '.php';

		if (File::exists($classPath)) {
			$this->error('Schedule: "' . $hook . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class.
		 * ---
		 */
		$stub = File::get(__DIR__ . '/../Stubs/Schedules/schedule.stub');
		$stub = str_replace(
			['{{ class_name }}', '{{ hook }}', '{{ interval }}', '{{ type }}'],
			[$className, $hook, $interval, $type],
			$stub
		);
		$stub = $this->replaceNamespaces($stub);

		File::ensureDirectoryExists(dirname($classPath));
		File::put($classPath, $stub);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/Schedules/schedule'.($type == 'wpsp' ? '-wpsp' : '').'.func');
		$func = str_replace(
			['{{ class_name }}', '{{ hook }}', '{{ interval }}', '{{ type }}'],
			[$className, $hook, $interval, $type],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/Schedules/schedule.use');
		$use = str_replace(
			['{{ class_name }}', '{{ hook }}', '{{ interval }}', '{{ type }}'],
			[$className, $hook, $interval, $type],
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
