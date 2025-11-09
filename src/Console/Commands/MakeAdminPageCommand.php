<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeAdminPageCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:admin-page')
			->setDescription('Create a new admin page.                  | Eg: bin/wpsp make:admin-page custom-admin-page --create-view')
			->setHelp('This command allows you to create an admin page.')
			->addArgument('path', InputArgument::OPTIONAL, 'The path of the admin page.')
			->addOption('create-view', 'create-view', InputOption::VALUE_NONE, 'Create view files for this admin page or not?');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$path = $input->getArgument('path');

		// If path is empty, ask questions.
		$helper = $this->getHelper('question');
		if (!$path) {
			$pathQuestion = new Question('Please enter the path of the admin page: ');
			$path         = $helper->ask($input, $output, $pathQuestion);

			if (empty($path)) {
				$this->writeln($output, 'Missing path for the admin page. Please try again.');
				return Command::INVALID;
			}

			$createViewQuestion = new ConfirmationQuestion('Do you want to create view files for this admin page? [y/N]: ', false);
			$createView         = $helper->ask($input, $output, $createViewQuestion);
		}

		// Define variables.
		$pathSlugify = Str::slug($path);
		$name        = $path;
		$nameSlugify = Str::slug($name, '_');
		$createView  = $createView ?? $input->getOption('create-view');

		// Check exist.
		$exist = FileSystem::exists($this->mainPath . '/app/Components/AdminPages/' . $nameSlugify . '.php');
		$exist = $exist || FileSystem::exists($this->mainPath . '/resources/views/modules/admin-pages/' . $path);
		if ($exist) {
			$this->writeln($output, '[ERROR] Admin page: "' . $path . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		if ($createView) {
			$content = FileSystem::get(__DIR__ . '/../Stubs/AdminPages/adminpage-view.stub');
		}
		else {
			$content = FileSystem::get(__DIR__ . '/../Stubs/AdminPages/adminpage.stub');
		}
		$content = str_replace('{{ className }}', $nameSlugify, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = str_replace('{{ name_slugify }}', $nameSlugify, $content);
		$content = str_replace('{{ path }}', $path, $content);
		$content = str_replace('{{ path_slugify }}', $pathSlugify, $content);
		$content = $this->replaceNamespaces($content);
		FileSystem::put($this->mainPath . '/app/Components/AdminPages/' . $nameSlugify . '.php', $content);

		// Create view files.
		if ($createView) {
			$bladeExt    = class_exists('\WPSPCORE\View\Blade') ? '.blade.php' : '.php';
			$nonBladeSep = class_exists('\WPSPCORE\View\Blade') ? '' : '/non-blade';

			// Create view directory.
			FileSystem::makeDirectory($this->mainPath . '/resources/views/modules/admin-pages/' . $path);

			// Create main view file.
			$view = FileSystem::get(__DIR__ . '/../Views/AdminPages'.$nonBladeSep.'/adminpage.view');
			$view = str_replace('{{ name }}', $name, $view);
			$view = str_replace('{{ name_slugify }}', $nameSlugify, $view);
			$view = str_replace('{{ path }}', $path, $view);
			$view = str_replace('{{ path_slugify }}', $pathSlugify, $view);
			FileSystem::put($this->mainPath . '/resources/views/modules/admin-pages/' . $path . '/main' . $bladeExt, $view);

			// Create dashboard view file.
			$view = FileSystem::get(__DIR__ . '/../Views/AdminPages'.$nonBladeSep.'/dashboard.view');
			$view = str_replace('{{ name }}', $name, $view);
			$view = str_replace('{{ name_slugify }}', $nameSlugify, $view);
			$view = str_replace('{{ path }}', $path, $view);
			$view = str_replace('{{ path_slugify }}', $pathSlugify, $view);
			FileSystem::put($this->mainPath . '/resources/views/modules/admin-pages/' . $path . '/dashboard' . $bladeExt, $view);

			// Create "Tab 1" view file.
			$view = FileSystem::get(__DIR__ . '/../Views/AdminPages'.$nonBladeSep.'/tab-1.view');
			$view = str_replace('{{ name }}', $name, $view);
			$view = str_replace('{{ name_slugify }}', $nameSlugify, $view);
			$view = str_replace('{{ path }}', $path, $view);
			$view = str_replace('{{ path_slugify }}', $pathSlugify, $view);
			FileSystem::put($this->mainPath . '/resources/views/modules/admin-pages/' . $path . '/tab-1' . $bladeExt, $view);

			// Create navigation view file.
			$view = FileSystem::get(__DIR__ . '/../Views/AdminPages'.$nonBladeSep.'/navigation.view');
			$view = str_replace('{{ name }}', $name, $view);
			$view = str_replace('{{ name_slugify }}', $nameSlugify, $view);
			$view = str_replace('{{ path }}', $path, $view);
			$view = str_replace('{{ path_slugify }}', $pathSlugify, $view);
			FileSystem::put($this->mainPath . '/resources/views/modules/admin-pages/' . $path . '/navigation' . $bladeExt, $view);
		}

		// Prepare new line for find function.
		$func = FileSystem::get(__DIR__ . '/../Funcs/AdminPages/adminpage.func');
		$func = str_replace('{{ name }}', $name, $func);
		$func = str_replace('{{ name_slugify }}', $nameSlugify, $func);
		$func = str_replace('{{ path }}', $path, $func);
		$func = str_replace('{{ path_slugify }}', $pathSlugify, $func);

		// Prepare new line for use class.
		$use = FileSystem::get(__DIR__ . '/../Uses/AdminPages/adminpage.use');
		$use = str_replace('{{ name }}', $name, $use);
		$use = str_replace('{{ name_slugify }}', $nameSlugify, $use);
		$use = str_replace('{{ path }}', $path, $use);
		$use = str_replace('{{ path_slugify }}', $pathSlugify, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('AdminPages', 'admin_pages', $func, $use);

		// Output message.
		$this->writeln($output, '<green>Created new admin page: "' . $path . '"</green>');

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