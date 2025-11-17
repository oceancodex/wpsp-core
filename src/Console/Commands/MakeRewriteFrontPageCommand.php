<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeRewriteFrontPageCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:rewrite-front-page
        {path? : The path of the rewrite front page.}
        {--rewrite-page-post-type= : The post type for rewrite front page.}
        {--rewrite-page-slug= : The page slug for rewrite front page.}
        {--use-template : Generate view using template.}';

	// Giữ nguyên spacing trước "| Eg:"
	protected $description = 'Create a new rewrite front page.          | Eg: bin/wpsp make:rewrite-front-page custom-rewrite-front-page --rewrite-page-post-type=page --rewrite-page-slug=parent/rewrite-front-pages --use-template';

	protected $help = 'This command allows you to create a rewrite front page.';

	public function handle(): void {
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

		// Normalize variables
		$pathSlugify = Str::slug($path, '-');
		$name        = $path;
		$nameSlugify = Str::slug($name, '_');

		$rewritePagePostType    = $rewritePagePostType ?? $this->option('rewrite-page-post-type') ?: 'page';
		$rewritePageSlug        = $rewritePageSlug ?? $this->option('rewrite-page-slug') ?: 'rewrite-front-pages';
		$rewritePageSlugSlugify = Str::slug($rewritePageSlug, '-');
		$useTemplate            = $useTemplate ?? $this->option('use-template') ?: false;

		// Check exists
		$componentPath = $mainPath . '/app/Components/RewriteFrontPages/' . $nameSlugify . '.php';
		$viewPath      = $mainPath . '/resources/views/modules/rewrite-front-pages/' . $path . '.blade.php';

		if (File::exists($componentPath) || File::exists($viewPath)) {
			$this->error('[ERROR] Rewrite front page: "' . $name . '" already exists! Please try again.');
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
				'{{ name_slugify }}',
				'{{ path }}',
				'{{ path_slugify }}',
				'{{ rewrite_page_post_type }}',
				'{{ rewrite_page_slug }}',
				'{{ rewrite_page_slug_slugify }}',
				'{{ use_template }}',
			],
			[
				$nameSlugify,
				$name,
				$nameSlugify,
				$path,
				$pathSlugify,
				$rewritePagePostType,
				$rewritePageSlug,
				$rewritePageSlugSlugify,
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
				'{{ name_slugify }}',
				'{{ path }}',
				'{{ path_slugify }}',
				'{{ rewrite_page_post_type }}',
				'{{ rewrite_page_slug }}',
				'{{ rewrite_page_slug_slugify }}',
			],
			[
				$name,
				$nameSlugify,
				$path,
				$pathSlugify,
				$rewritePagePostType,
				$rewritePageSlug,
				$rewritePageSlugSlugify,
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
				'{{ name_slugify }}',
				'{{ path }}',
				'{{ path_slugify }}',
				'{{ rewrite_page_post_type }}',
				'{{ rewrite_page_slug }}',
				'{{ rewrite_page_slug_slugify }}',
			],
			[
				$name,
				$nameSlugify,
				$path,
				$pathSlugify,
				$rewritePagePostType,
				$rewritePageSlug,
				$rewritePageSlugSlugify,
			],
			$func
		);

		$use = File::get(__DIR__ . '/../Uses/RewriteFrontPages/rewritefrontpage.use');
		$use = str_replace(
			[
				'{{ name }}',
				'{{ name_slugify }}',
				'{{ path }}',
				'{{ path_slugify }}',
				'{{ rewrite_page_post_type }}',
				'{{ rewrite_page_slug }}',
				'{{ rewrite_page_slug_slugify }}',
			],
			[
				$name,
				$nameSlugify,
				$path,
				$pathSlugify,
				$rewritePagePostType,
				$rewritePageSlug,
				$rewritePageSlugSlugify,
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
