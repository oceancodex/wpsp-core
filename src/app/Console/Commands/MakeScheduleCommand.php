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

	protected $description = 'Create a new schedule.                    | Eg: bin/wpsp make:schedule custom_schedule_hook hourly';

	protected $help = 'This command allows you to create a schedule.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		$hook     = $this->argument('hook');
		$interval = $this->argument('interval');

		// Ask interactively
		if (!$hook) {
			$hook = $this->ask('Please enter the hook of the schedule');

			if (empty($hook)) {
				$this->error('Missing hook for the schedule. Please try again.');
				exit;
			}
		}

		if (!$interval) {
			$interval = $this->ask('Please enter the interval of the schedule (Leave empty = hourly)');
		}

		$interval = empty($interval) ? 'hourly' : $interval;

		// Normalize variables
		$hookSlugify     = Str::slug($hook, '_');
		$intervalSlugify = Str::slug($interval, '_');

		// Path
		$path = $mainPath . '/app/WordPress/Schedules/' . $hookSlugify . '.php';

		// Check exists
		if (File::exists($path)) {
			$this->error('[ERROR] Schedule: "' . $hookSlugify . '" already exists! Please try again.');
			exit;
		}

		// Load stub
		$content = File::get(__DIR__ . '/../Stubs/Schedules/schedule.stub');

		$content = str_replace(
			['{{ className }}', '{{ hook }}', '{{ hook_slugify }}', '{{ interval }}', '{{ interval_slugify }}'],
			[$hookSlugify, $hook, $hookSlugify, $interval, $intervalSlugify],
			$content
		);

		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($path));
		File::put($path, $content);

		// Func registration
		$func = File::get(__DIR__ . '/../Funcs/Schedules/schedule.func');
		$func = str_replace(
			['{{ hook }}', '{{ hook_slugify }}', '{{ interval }}', '{{ interval_slugify }}'],
			[$hook, $hookSlugify, $interval, $intervalSlugify],
			$func
		);

		// Use registration
		$use = File::get(__DIR__ . '/../Uses/Schedules/schedule.use');
		$use = str_replace(
			['{{ hook }}', '{{ hook_slugify }}', '{{ interval }}', '{{ interval_slugify }}'],
			[$hook, $hookSlugify, $interval, $intervalSlugify],
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
