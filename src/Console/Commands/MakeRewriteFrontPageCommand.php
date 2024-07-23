<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Filesystem\Filesystem;
use WPSPCORE\Traits\CommandsTrait;

class MakeRewriteFrontPageCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:rewrite-front-page')
			->setDescription('Create a new rewrite front page.          | Eg: bin/console make:rewrite-front-page custom-rewrite-front-page --rewrite-page-slug=parent/rewrite-front-pages --use-template')
			->setHelp('This command allows you to create a rewrite front page.')
			->addArgument('path', InputArgument::OPTIONAL, 'The path of the rewrite front page.')
			->addOption('rewrite-page-slug', 'rewrite-page-slug', InputOption::VALUE_REQUIRED, 'The page slug for rewrite front page.')
			->addOption('use-template', 'use-template', InputOption::VALUE_NONE, 'Whether this rewrite front page have use template or not?.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$path = $input->getArgument('path');

		// If path is empty.
		$helper = $this->getHelper('question');
		if (!$path) {
			$pathQuestion = new Question('Please enter the path of the rewrite front page: ');
			$path         = $helper->ask($input, $output, $pathQuestion);

			if (empty($path)) {
				$output->writeln('Missing path for the rewrite front page. Please try again.');
				return Command::INVALID;
			}

			$rewritePageSlugQuestion = new Question('Please enter the page name for rewrite front page: ', 'rewrite-front-pages');
			$rewritePageSlug         = $helper->ask($input, $output, $rewritePageSlugQuestion);

			$useTemplateQuestion = new ConfirmationQuestion('Use template for this rewrite front page? [y/N]: ', false);
			$useTemplate         = $helper->ask($input, $output, $useTemplateQuestion);
		}

		// Define variables.
		$pathSlugify            = Str::slug($path, '-');
		$name                   = $path;
		$nameSlugify            = Str::slug($name, '_');
		$rewritePageSlug        = $rewritePageSlug ?? $input->getOption('rewrite-page-slug') ?: 'rewrite-front-pages';
		$rewritePageSlugSlugify = Str::slug($rewritePageSlug, '-');
		$useTemplate            = $useTemplate ?? $input->getOption('use-template') ?: false;

		// Check exist.
		$exist = Filesystem::exists($this->mainPath . '/app/Extend/Components/RewriteFrontPages/' . $nameSlugify . '.php');
		$exist = $exist || Filesystem::exists($this->mainPath . '/resources/views/modules/web/rewrite-front-pages/' . $pathSlugify . '.php');
		if ($exist) {
			$output->writeln('[ERROR] Rewrite front page: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		$content = Filesystem::get(__DIR__ . '/../Stubs/RewriteFrontPages/rewritefrontpage.stub');
		$content = str_replace('{{ className }}', $nameSlugify, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = str_replace('{{ name_slugify }}', $nameSlugify, $content);
		$content = str_replace('{{ path }}', $path, $content);
		$content = str_replace('{{ path_slugify }}', $pathSlugify, $content);
		$content = str_replace('{{ rewrite_page_slug }}', $rewritePageSlug, $content);
		$content = str_replace('{{ rewrite_page_slug_slugify }}', $rewritePageSlugSlugify, $content);
		$content = str_replace('{{ use_template }}', $useTemplate ? 'true' : 'false', $content);
		$content = $this->replaceNamespaces($content);
		Filesystem::put($this->mainPath . '/app/Extend/Components/RewriteFrontPages/' . $nameSlugify . '.php', $content);

		// Create view file.
		if ($useTemplate) {
			$view = Filesystem::get(__DIR__ . '/../Views/RewriteFrontPages/rewritefrontpage.view');
		}
		else {
			$view = Filesystem::get(__DIR__ . '/../Views/RewriteFrontPages/rewritefrontpage-no-template.view');
		}
		$view = str_replace('{{ name }}', $name, $view);
		$view = str_replace('{{ name_slugify }}', $nameSlugify, $view);
		$view = str_replace('{{ path }}', $path, $view);
		$view = str_replace('{{ path_slugify }}', $pathSlugify, $view);
		$view = str_replace('{{ rewrite_page_slug }}', $rewritePageSlug, $view);
		$view = str_replace('{{ rewrite_page_slug_slugify }}', $rewritePageSlugSlugify, $view);
		Filesystem::put($this->mainPath . '/resources/views/modules/web/rewrite-front-pages/' . $path . '.blade.php', $view);

		// Prepare new line for find function.
		$func = Filesystem::get(__DIR__ . '/../Funcs/RewriteFrontPages/rewritefrontpage.func');
		$func = str_replace('{{ name }}', $name, $func);
		$func = str_replace('{{ name_slugify }}', $nameSlugify, $func);
		$func = str_replace('{{ path }}', $path, $func);
		$func = str_replace('{{ path_slugify }}', $pathSlugify, $func);
		$func = str_replace('{{ rewrite_page_slug }}', $rewritePageSlug, $func);
		$func = str_replace('{{ rewrite_page_slug_slugify }}', $rewritePageSlugSlugify, $func);

		// Prepare new line for use class.
		$use = Filesystem::get(__DIR__ . '/../Uses/RewriteFrontPages/rewritefrontpage.use');
		$use = str_replace('{{ name }}', $name, $use);
		$use = str_replace('{{ name_slugify }}', $nameSlugify, $use);
		$use = str_replace('{{ path }}', $path, $use);
		$use = str_replace('{{ path_slugify }}', $pathSlugify, $use);
		$use = str_replace('{{ rewrite_page_slug }}', $rewritePageSlug, $use);
		$use = str_replace('{{ rewrite_page_slug_slugify }}', $rewritePageSlugSlugify, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('RewriteFrontPages', 'rewrite_front_pages', $func, $use);

		// Output message.
		$output->writeln('Created new rewrite front page: "' . $path . '"');

		// this method must return an integer number with the "exit status code"
		// of the command. You can also use these constants to make code more readable

		// return this if there was no problem running the command
		// (it's equivalent to returning int(0))
		return Command::SUCCESS;

		// or return this if some error happened during the execution
		// (it's equivalent to returning int(1))
//		 return Command::FAILURE;

		// or return this to indicate incorrect command usage; e.g. invalid options
		// or missing arguments (it's equivalent to returning int(2))
		// return Command::INVALID
	}

}