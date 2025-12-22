<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeRewriteFrontPageCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:rewrite-front-page
        {path? : The path of the rewrite front page.}
        {--method= : The method for rewrite front page.}
        {--post-type= : The post type for rewrite front page.}
        {--page-slug= : The page slug for rewrite front page.}
        {--template : Generate view using template.}';

	protected $description = 'Create a new rewrite front page. | Eg: php artisan make:rewrite-front-page custom-rewrite-front-page --method=GET --post-type=page --page-slug=parent/rewrite-front-pages --template';

	protected $help = 'This command allows you to create a rewrite front page.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		$path = $this->argument('path');

		// Ask interactively if missing
		if (!$path) {
			$path = $this->ask('Please enter the path of the rewrite front page (Eg: custom-rewrite-front-page)');

			if (empty($path)) {
				$this->error('Missing path for the rewrite front page. Please try again.');
				exit;
			}

			$rewritePagePostType = $this->ask('Please enter the post type for rewrite front page (Eg: page,...)', 'page');
			$rewritePageSlug     = $this->ask('Please enter the page name for rewrite front page (Eg: page-for-rewrite-rules,...)', 'rewrite-front-pages');
			$useTemplate         = $this->confirm('Use template for this rewrite front page?', false);
		}

		// Define variables
		$name = Str::slug($path, '_');

		// Không cần validate "name", vì command này yêu cầu "path" mà path có thể chứa "-".
		// $name sẽ được slugify từ "path" ra.

		$method                 = strtolower($this->option('method') ?: 'GET');
		$rewritePagePostType    = $rewritePagePostType ?? $this->option('post-type') ?: 'page';
		$rewritePageSlug        = $rewritePageSlug ?? $this->option('page-slug') ?: 'rewrite-front-pages';
		$useTemplate            = $useTemplate ?? $this->option('template') ?: false;

		// Check exists
		$componentPath = $mainPath . '/app/WordPress/RewriteFrontPages/' . $name . '.php';
		$viewPath      = $mainPath . '/resources/views/rewrite-front-pages/' . $path . '.blade.php';

		if (File::exists($componentPath) || File::exists($viewPath)) {
			$this->error('Rewrite front page: "' . $name . '" already exists! Please try again.');
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

		$this->info('Created new rewrite front page: "' . $path . '"');

		exit;
	}

}
