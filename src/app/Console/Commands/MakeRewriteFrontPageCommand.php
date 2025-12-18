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
        {--rewrite-page-post-type= : The post type for rewrite front page.}
        {--rewrite-page-slug= : The page slug for rewrite front page.}
        {--use-template : Generate view using template.}';

	protected $description = 'Create a new rewrite front page. | Eg: bin/wpsp make:rewrite-front-page custom-rewrite-front-page --rewrite-page-post-type=page --rewrite-page-slug=parent/rewrite-front-pages --use-template';

	protected $help = 'This command allows you to create a rewrite front page.';

	public function handle() {
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		$path = $this->argument('path');

		// Ask interactively if missing
		if (!$path) {
			$path = $this->ask('Please enter the path of the rewrite front page');

			if (empty($path)) {
				$this->error('Missing path for the rewrite front page. Please try again.');
				exit;
			}

			$rewritePagePostType = $this->ask('Please enter the post type for rewrite front page', 'page');
			$rewritePageSlug     = $this->ask('Please enter the page name for rewrite front page', 'rewrite-front-pages');
			$useTemplate         = $this->confirm('Use template for this rewrite front page?', false);
		}

		// Define variables
		$name = Str::slug($path, '_');

		// Không cần validate "name", vì command này yêu cầu "path" mà path có thể chứa "-".
		// $name sẽ được slugify từ "path" ra.

		$rewritePagePostType    = $rewritePagePostType ?? $this->option('rewrite-page-post-type') ?: 'page';
		$rewritePageSlug        = $rewritePageSlug ?? $this->option('rewrite-page-slug') ?: 'rewrite-front-pages';
		$useTemplate            = $useTemplate ?? $this->option('use-template') ?: false;

		// Check exists
		$componentPath = $mainPath . '/app/WordPress/RewriteFrontPages/' . $name . '.php';
		$viewPath      = $mainPath . '/resources/views/modules/rewrite-front-pages/' . $path . '.blade.php';

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
				'{{ rewrite_page_post_type }}',
				'{{ rewrite_page_slug }}',
				'{{ use_template }}',
			],
			[
				$name,
				$name,
				$path,
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
				'{{ rewrite_page_post_type }}',
				'{{ rewrite_page_slug }}',
			],
			[
				$name,
				$path,
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
				'{{ rewrite_page_post_type }}',
				'{{ rewrite_page_slug }}',
			],
			[
				$name,
				$path,
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
				'{{ rewrite_page_post_type }}',
				'{{ rewrite_page_slug }}',
			],
			[
				$name,
				$path,
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
