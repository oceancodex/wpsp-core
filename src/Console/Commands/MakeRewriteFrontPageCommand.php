<?php

namespace OCBPCORE\Console\Commands;

use OCBPCORE\Objects\File\FileHandler;
use OCBPCORE\Objects\Slugify\Slugify;
use OCBPCORE\Traits\CommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class MakeRewriteFrontPageCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:rewrite-front-page')
			->setDescription('Create a new rewrite front page.  | Eg: bin/console make:rewrite-front-page custom-rewrite-front-page --rewrite-page-name=rewrite-front-pages --use-template')
			->setHelp('This command allows you to create a rewrite front page.')
			->addArgument('path', InputArgument::OPTIONAL, 'The path of the rewrite front page.')
			->addOption('rewrite-page-name', 'rewrite-page-name', InputOption::VALUE_REQUIRED, 'The page name for rewrite front page.')
			->addOption('use-template', 'use-template', InputOption::VALUE_NONE, 'Whether this rewrite front page have use template or not?.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$path = $input->getArgument('path');

		// If path is empty.
		$helper = $this->getHelper('question');
		if (!$path) {
			$pathQuestion = new Question('Please enter the path of the rewrite front page: ', 'custom-rewrite-front-page');
			$path         = $helper->ask($input, $output, $pathQuestion);

			if (empty($path)) {
				$output->writeln('Missing path for the rewrite front page. Please try again.');
				return Command::INVALID;
			}

			$rewritePageNameQuestion = new Question('Please enter the page name for rewrite front page: ', 'rewrite-front-pages');
			$rewritePageName         = $helper->ask($input, $output, $rewritePageNameQuestion);

			$useTemplateQuestion = new ConfirmationQuestion('Use template for this rewrite front page? [y/N]: ', false);
			$useTemplate         = $helper->ask($input, $output, $useTemplateQuestion);
		}

		// Define variables.
		$pathSlugify            = Slugify::slugUnify($path, '-');
		$name                   = $path;
		$nameSlugify            = Slugify::slugUnify($name, '_');
		$rewritePageName        = $rewritePageName ?? $input->getOption('rewrite-page-name') ?: 'rewrite-front-pages';
		$rewritePageNameSlugify = Slugify::slugUnify($rewritePageName, '-');
		$useTemplate            = $useTemplate ?? $input->getOption('use-template') ?: false;

		// Check exist.
		$exist = FileHandler::getFileSystem()->exists(__DIR__ . '/../../Extend/Components/RewriteFrontPages/' . $nameSlugify . '.php');
		$exist = $exist || FileHandler::getFileSystem()->exists(__DIR__ . '/../../../resources/views/modules/web/rewrite-front-pages/' . $pathSlugify . '.php');
		if ($exist) {
			$output->writeln('[ERROR] Rewrite front page: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		$content = FileHandler::getFileSystem()->get(__DIR__ . '/../Stubs/RewriteFrontPages/rewritefrontpage.stub');
		$content = str_replace('{{ className }}', $nameSlugify, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = str_replace('{{ name_slugify }}', $nameSlugify, $content);
		$content = str_replace('{{ path }}', $path, $content);
		$content = str_replace('{{ path_slugify }}', $pathSlugify, $content);
		$content = str_replace('{{ rewrite_page_name }}', $rewritePageName, $content);
		$content = str_replace('{{ rewrite_page_name_slugify }}', $rewritePageNameSlugify, $content);
		$content = str_replace('{{ use_template }}', $useTemplate ? 'true' : 'false', $content);
		$content = $this->replaceRootNamespace($content);
		FileHandler::saveFile($content, __DIR__ . '/../../Extend/Components/RewriteFrontPages/' . $nameSlugify . '.php');

		// Create view file.
		if ($useTemplate) {
			$view = FileHandler::getFileSystem()->get(__DIR__ . '/../Views/RewriteFrontPages/rewritefrontpage.view');
		}
		else {
			$view = FileHandler::getFileSystem()->get(__DIR__ . '/../Views/RewriteFrontPages/rewritefrontpage-no-template.view');
		}
		$view = str_replace('{{ name }}', $name, $view);
		$view = str_replace('{{ name_slugify }}', $nameSlugify, $view);
		$view = str_replace('{{ path }}', $path, $view);
		$view = str_replace('{{ path_slugify }}', $pathSlugify, $view);
		$view = str_replace('{{ rewrite_page_name }}', $rewritePageName, $view);
		$view = str_replace('{{ rewrite_page_name_slugify }}', $rewritePageNameSlugify, $view);
		FileHandler::saveFile($view, __DIR__ . '/../../../resources/views/modules/web/rewrite-front-pages/' . $path . '.blade.php');

		// Prepare new line for find function.
		$func = FileHandler::getFileSystem()->get(__DIR__ . '/../Funcs/RewriteFrontPages/rewritefrontpage.func');
		$func = str_replace('{{ name }}', $name, $func);
		$func = str_replace('{{ name_slugify }}', $nameSlugify, $func);
		$func = str_replace('{{ path }}', $path, $func);
		$func = str_replace('{{ path_slugify }}', $pathSlugify, $func);
		$func = str_replace('{{ rewrite_page_name }}', $rewritePageName, $func);
		$func = str_replace('{{ rewrite_page_name_slugify }}', $rewritePageNameSlugify, $func);

		// Prepare new line for use class.
		$use = FileHandler::getFileSystem()->get(__DIR__ . '/../Uses/RewriteFrontPages/rewritefrontpage.use');
		$use = str_replace('{{ name }}', $name, $use);
		$use = str_replace('{{ name_slugify }}', $nameSlugify, $use);
		$use = str_replace('{{ path }}', $path, $use);
		$use = str_replace('{{ path_slugify }}', $pathSlugify, $use);
		$use = str_replace('{{ rewrite_page_name }}', $rewritePageName, $use);
		$use = str_replace('{{ rewrite_page_name_slugify }}', $rewritePageNameSlugify, $use);
		$use = $this->replaceRootNamespace($use);

		// Add class to route.
		$this->addClassToWebRoute('rewrite_front_pages', $func, $use);

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