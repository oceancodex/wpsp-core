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
        {interval? : The interval of the schedule.}';

	protected $description = 'Create a new schedule. | Eg: php artisan make:schedule custom_schedule_hook hourly';

	protected $help = 'This command allows you to create a schedule.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		$hook     = $this->argument('hook');
		$interval = $this->argument('interval');

		// Ask interactively
		if (!$hook) {
			$hook = $this->ask('Please enter the hook of the schedule (Eg: custom_schedule_hook)');

			if (empty($hook)) {
				$this->error('Missing hook for the schedule. Please try again.');
				exit;
			}

			if (!$interval) {
				$interval = $this->ask('Please enter the interval of the schedule', 'hourly');
			}
		}

		$interval = empty($interval) ? 'hourly' : $interval;

		// Validate
		$this->validateClassName($hook, 'hook');
		$this->validateClassName($interval, 'interval');

		// Path
		$path = $mainPath . '/app/WordPress/Schedules/' . $hook . '.php';

		// Check exists
		if (File::exists($path)) {
			$this->error('Schedule: "' . $hook . '" already exists! Please try again.');
			exit;
		}

		// Load stub
		$content = File::get(__DIR__ . '/../Stubs/Schedules/schedule.stub');

		$content = str_replace(
			['{{ className }}', '{{ hook }}', '{{ interval }}'],
			[$hook, $hook, $interval],
			$content
		);

		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		// Func registration
		$func = File::get(__DIR__ . '/../Funcs/Schedules/schedule.func');
		$func = str_replace(
			['{{ hook }}', '{{ interval }}'],
			[$hook, $interval],
			$func
		);

		// Use registration
		$use = File::get(__DIR__ . '/../Uses/Schedules/schedule.use');
		$use = str_replace(
			['{{ hook }}', '{{ interval }}'],
			[$hook, $interval],
			$use
		);
		$use = $this->replaceNamespaces($use);

		// Add to route
		$this->addClassToRoute('Schedules', 'schedules', $func, $use);

		// Done
		$this->info('Created new schedule: "' . $hook . '"');

		exit;
	}

}
