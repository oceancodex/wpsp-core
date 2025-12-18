<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeUserMetaBoxCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:user-meta-box
        {id? : The id of the user meta box}
        {--view : Create view files for this user meta box}';

	protected $description = 'Create a new user meta box.               | Eg: bin/wpsp make:user-meta-box custom_user_meta_box --view';

	protected $help = 'This command allows you to create a user meta box.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		$id = $this->argument('id');

		/* -------------------------------------------------
		 *  ASK INTERACTIVE
		 * ------------------------------------------------- */
		if (!$id) {
			$id = $this->ask('Please enter the ID of the user meta box');

			if (empty($id)) {
				$this->error('Missing ID for the user meta box. Please try again.');
				exit;
			}

			$createView = $this->confirm('Do you want to create view files for this user meta box?', false);
		}

		// Define variables
		$createView = $createView ?? $this->option('view');

		// Validate
		$this->validateClassName($id, 'id');

		/* -------------------------------------------------
		 *  CHECK EXISTS
		 * ------------------------------------------------- */
		$classPath = $mainPath . '/app/WordPress/UserMetaBoxes/' . $id . '.php';
		$viewDir   = $mainPath . '/resources/views/modules/user-meta-boxes/' . $id;

		if (File::exists($classPath) || File::exists($viewDir)) {
			$this->error('User meta box: "' . $id . '" already exists! Please try again.');
			exit;
		}

		/* -------------------------------------------------
		 *  CREATE CLASS FILE
		 * ------------------------------------------------- */
		if ($createView) {
			$content = File::get(__DIR__ . '/../Stubs/UserMetaBoxes/user-meta-box-view.stub');
		}
		else {
			$content = File::get(__DIR__ . '/../Stubs/UserMetaBoxes/user-meta-box.stub');
		}

		$content = str_replace(
			['{{ className }}', '{{ id }}'],
			[$id, $id],
			$content
		);
		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($classPath));
		File::put($classPath, $content);

		/* -------------------------------------------------
		 *  CREATE VIEW FILES
		 * ------------------------------------------------- */
		if ($createView) {
			$bladeExt    = class_exists('\WPSPCORE\View\Blade') ? '.blade.php' : '.php';
			$nonBladeSep = class_exists('\WPSPCORE\View\Blade') ? '' : '/non-blade';

			File::ensureDirectoryExists($viewDir);

			$views = [
				'main'       => 'main.view',
				'tab-1'      => 'tab-1.view',
				'tab-2'      => 'tab-2.view',
				'navigation' => 'navigation.view',
			];

			foreach ($views as $file => $stubFile) {
				$view = File::get(__DIR__ . '/../Views/UserMetaBoxes' . $nonBladeSep . '/' . $stubFile);

				$view = str_replace(
					['{{ className }}', '{{ id }}'],
					[$id, $id],
					$view
				);

				File::put("$viewDir/{$file}{$bladeExt}", $view);
			}
		}

		/* -------------------------------------------------
		 *  REGISTER ROUTE ENTRY (func + use)
		 * ------------------------------------------------- */
		$func = File::get(__DIR__ . '/../Funcs/UserMetaBoxes/user-meta-box.func');
		$func = str_replace(
			['{{ id }}'],
			[$id],
			$func
		);

		$use = File::get(__DIR__ . '/../Uses/UserMetaBoxes/user-meta-box.use');
		$use = str_replace(
			['{{ id }}'],
			[$id],
			$use
		);
		$use = $this->replaceNamespaces($use);

		$this->addClassToRoute('UserMetaBoxes', 'user_meta_boxes', $func, $use);

		/* -------------------------------------------------
		 *  DONE
		 * ------------------------------------------------- */
		$this->info('Created new user meta box: "' . $id . '"');

		exit;
	}

}
