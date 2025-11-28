<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeMetaBoxCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:meta-box
        {id? : The ID of the meta box.}
        {--create-view : Create a view file for this meta box}';

	protected $description = 'Create a new meta box.                    | Eg: bin/wpsp make:meta-box custom_meta_box --create-view';

	protected $help = 'This command allows you to create a meta box.';

	public function handle(): void {
		$this->funcs = $this->getLaravel()->make('funcs');
		$mainPath    = $this->funcs->mainPath;

		$id = $this->argument('id');

		// Interactive questions
		if (!$id) {
			$id = $this->ask('Please enter the ID of the meta box');

			if (empty($id)) {
				$this->error('Missing ID for the meta box. Please try again.');
				exit;
			}

			$createView = $this->confirm('Do you want to create view files for this meta box?', false);
		}
		else {
			$createView = $this->option('create-view');
		}

		// Normalize
		$idSlugify = Str::slug($id, '_');

		// Check exists
		$componentPath = $mainPath . '/app/WP/MetaBoxes/' . $idSlugify . '.php';
		$viewPath      = $mainPath . '/resources/views/modules/meta-boxes/' . $id . '.blade.php';

		if (File::exists($componentPath)) {
			$this->error('[ERROR] Meta box "' . $id . '" already exists! Please try again.');
			exit;
		}

		/* ---- Create view ---- */
		if ($createView) {
			File::ensureDirectoryExists(dirname($viewPath));

			$view = File::get(__DIR__ . '/../Views/MetaBoxes/meta-box.view');
			$view = str_replace(['{{ id }}', '{{ id_slugify }}'], [$id, $idSlugify], $view);

			File::put($viewPath, $view);

			$content = File::get(__DIR__ . '/../Stubs/MetaBoxes/meta-box-view.stub');
		}
		else {
			$content = File::get(__DIR__ . '/../Stubs/MetaBoxes/meta-box.stub');
		}

		/* ---- Create class file ---- */
		$content = str_replace(
			['{{ className }}', '{{ id }}', '{{ id_slugify }}'],
			[$idSlugify, $id, $idSlugify],
			$content
		);

		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($componentPath));
		File::put($componentPath, $content);

		/* ---- Register in Funcs/Uses ---- */
		$func = File::get(__DIR__ . '/../Funcs/MetaBoxes/meta-box.func');
		$func = str_replace(['{{ id }}', '{{ id_slugify }}'], [$id, $idSlugify], $func);

		$use = File::get(__DIR__ . '/../Uses/MetaBoxes/meta-box.use');
		$use = str_replace(['{{ id }}', '{{ id_slugify }}'], [$id, $idSlugify], $use);
		$use = $this->replaceNamespaces($use);

		// Add to route
		$this->addClassToRoute('MetaBoxes', 'meta_boxes', $func, $use);

		/* ---- Done ---- */
		$this->info('Created new meta box: "' . $id . '"');

		exit;
	}

}
