<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeMetaBoxCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:meta-box
        {id? : The ID of the meta box.}
        {--view : Create a view file for this meta box}';

	protected $description = 'Create a new meta box. | Eg: bin/wpsp make:meta-box custom_meta_box --view';

	protected $help = 'This command allows you to create a meta box.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		$id = $this->argument('id');

		// Interactive questions
		if (!$id) {
			$id = $this->ask('Please enter the ID of the meta box (Eg: custom_meta_box)');

			if (empty($id)) {
				$this->error('Missing ID for the meta box. Please try again.');
				exit;
			}

			$createView = $this->confirm('Do you want to create view files for this meta box?', false);
		}
		else {
			$createView = $this->option('view');
		}

		// Validate
		$this->validateClassName($id, 'id');

		// Check exists
		$componentPath = $mainPath . '/app/WordPress/MetaBoxes/' . $id . '.php';
		$viewPath      = $mainPath . '/resources/views/modules/meta-boxes/' . $id . '.blade.php';

		if (File::exists($componentPath)) {
			$this->error('Meta box "' . $id . '" already exists! Please try again.');
			exit;
		}

		/* ---- Create view ---- */
		if ($createView) {
			File::ensureDirectoryExists(dirname($viewPath));

			$view = File::get(__DIR__ . '/../Views/MetaBoxes/meta-box.view');
			$view = str_replace(['{{ id }}'], [$id], $view);

			File::put($viewPath, $view);

			$content = File::get(__DIR__ . '/../Stubs/MetaBoxes/meta-box-view.stub');
		}
		else {
			$content = File::get(__DIR__ . '/../Stubs/MetaBoxes/meta-box.stub');
		}

		/* ---- Create class file ---- */
		$content = str_replace(
			['{{ className }}', '{{ id }}'],
			[$id, $id],
			$content
		);

		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($componentPath));
		File::put($componentPath, $content);

		/* ---- Register in Funcs/Uses ---- */
		$func = File::get(__DIR__ . '/../Funcs/MetaBoxes/meta-box.func');
		$func = str_replace(['{{ id }}'], [$id], $func);

		$use = File::get(__DIR__ . '/../Uses/MetaBoxes/meta-box.use');
		$use = str_replace(['{{ id }}'], [$id], $use);
		$use = $this->replaceNamespaces($use);

		// Add to route
		$this->addClassToRoute('MetaBoxes', 'meta_boxes', $func, $use);

		/* ---- Done ---- */
		$this->info('Created new meta box: "' . $id . '"');

		exit;
	}

}
