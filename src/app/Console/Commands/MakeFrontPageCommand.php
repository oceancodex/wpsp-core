<?php

namespace WPSPCORE\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeFrontPageCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:front-page
        {path? : The path of the front page.}
        {--method= : The method for front page.}';

	protected $description = 'Create a new front page. | Eg: php artisan make:front-page custom-front-page --method=GET';

	protected $help = 'This command allows you to create a front page.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		$path = $this->argument('path');

		// Ask interactively if missing
		if (!$path) {
			$path = $this->ask('Please enter the path of the front page (Eg: custom-front-page)');

			if (empty($path)) {
				$this->error('Missing path for the front page. Please try again.');
				exit;
			}

			$method = $this->ask('Please enter the HTTP method for the front page', 'GET');
		}
		else {
			$method = $this->option('method');
		}

		// Define variables
		$name = Str::slug($path, '_');
		$method = strtolower($method ?: 'GET');

		// Không cần validate "name", vì command này yêu cầu "path" mà path có thể chứa "-".
		// $name sẽ được slugify từ "path" ra.

		// Check exists
		$componentPath = $mainPath . '/app/WordPress/FrontPages/' . $name . '.php';
		$viewPath      = $mainPath . '/resources/views/front-pages/' . $path . '.blade.php';

		if (File::exists($componentPath) || File::exists($viewPath)) {
			$this->error('Front page: "' . $path . '" already exists! Please try again.');
			exit;
		}

		/* -------------------------
		 *  Create class file
		 * ------------------------- */
		$content = File::get(__DIR__ . '/../Stubs/RewriteFrontPages/rewritefrontpage.stub');
		$content = str_replace(
			[
				'{{ className }}',
				'{{ name }}',
				'{{ path }}',
				'{{ method }}',
				'{{ postType }}',
				'{{ pageSlug }}',
				'{{ useTemplate }}',
			],
			[
				$name,
				$name,
				$path,
				$method,
				$rewritePagePostType,
				$rewritePageSlug,
				$useTemplate ? 'true' : 'false',
			],
			$content
		);

		$content = $this->replaceNamespaces($content);

		File::ensureDirectoryExists(dirname($componentPath));
		File::put($componentPath, $content);

		/* -------------------------
		 *  Create view file
		 * ------------------------- */
		$viewStubPath = $useTemplate
			? __DIR__ . '/../Views/RewriteFrontPages/rewritefrontpage.view'
			: __DIR__ . '/../Views/RewriteFrontPages/rewritefrontpage-no-template.view';

		$view = File::get($viewStubPath);
		$view = str_replace(
			[
				'{{ name }}',
				'{{ path }}',
				'{{ method }}',
				'{{ postType }}',
				'{{ pageSlug }}',
			],
			[
				$name,
				$path,
				$method,
				$rewritePagePostType,
				$rewritePageSlug,
			],
			$view
		);

		File::ensureDirectoryExists(dirname($viewPath));
		File::put($viewPath, $view);

		/* -------------------------
		 *  Func + Use registration
		 * ------------------------- */
		$func = File::get(__DIR__ . '/../Funcs/RewriteFrontPages/rewritefrontpage.func');
		$func = str_replace(
			[
				'{{ name }}',
				'{{ path }}',
				'{{ method }}',
				'{{ postType }}',
				'{{ pageSlug }}',
			],
			[
				$name,
				$path,
				$method,
				$rewritePagePostType,
				$rewritePageSlug,
			],
			$func
		);

		$use = File::get(__DIR__ . '/../Uses/RewriteFrontPages/rewritefrontpage.use');
		$use = str_replace(
			[
				'{{ name }}',
				'{{ path }}',
				'{{ method }}',
				'{{ postType }}',
				'{{ pageSlug }}',
			],
			[
				$name,
				$path,
				$method,
				$rewritePagePostType,
				$rewritePageSlug,
			],
			$use
		);

		$use = $this->replaceNamespaces($use);

		// Register class
		$this->addClassToRoute('RewriteFrontPages', 'rewrite_front_pages', $func, $use);

		$this->info('Created new front page: "' . $path . '"');

		exit;
	}

}