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

class MakeUserMetaBoxCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:user-meta-box')
			->setDescription('Create a new user meta box.               | Eg: bin/wpsp make:user-meta-box custom_user_meta_box --create-view')
			->setHelp('This command allows you to create an user meta box.')
			->addArgument('id', InputArgument::OPTIONAL, 'The id of the user meta box.')
			->addOption('create-view', 'create-view', InputOption::VALUE_NONE, 'Create view files for this user meta box or not?');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$id = $input->getArgument('id');

		$helper = $this->getHelper('question');
		if (!$id) {
			$idQuestion = new Question('Please enter the ID of the user meta box: ');
			$id         = $helper->ask($input, $output, $idQuestion);

			if (empty($id)) {
				$this->writeln($output, 'Missing ID for the user meta box. Please try again.');
				return Command::INVALID;
			}

			$createViewQuestion = new ConfirmationQuestion('Do you want to create view files for this user meta box? [y/N]: ', false);
			$createView         = $helper->ask($input, $output, $createViewQuestion);
		}
		$idSlugify  = Str::slug($id, '_');
		$createView = $createView ?? $input->getOption('create-view');

		// Check exist.
		$exist = FileSystem::exists($this->mainPath . '/app/Components/UserMetaBoxes/' . $idSlugify . '.php');
		$exist = $exist || FileSystem::exists(__DIR__ . '/../../../resources/views/modules/user-meta-boxes/'. $idSlugify . '.blade.php');
		if ($exist) {
			$this->writeln($output, '[ERROR] User meta box: "' . $id . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		if ($createView) {
			$content = FileSystem::get(__DIR__ . '/../Stubs/UserMetaBoxes/user-meta-box-view.stub');
		}
		else {
			$content = FileSystem::get(__DIR__ . '/../Stubs/UserMetaBoxes/user-meta-box.stub');
		}
		$content = str_replace('{{ className }}', $idSlugify, $content);
		$content = str_replace('{{ id }}', $id, $content);
		$content = str_replace('{{ id_slugify }}', $idSlugify, $content);
		$content = $this->replaceNamespaces($content);
		FileSystem::put($this->mainPath . '/app/Components/UserMetaBoxes/' . $idSlugify . '.php', $content);

		// Create view files.
		if ($createView) {
			$bladeExt    = class_exists('\WPSPCORE\View\Blade') ? '.blade.php' : '.php';
			$nonBladeSep = class_exists('\WPSPCORE\View\Blade') ? '' : '/non-blade';

			// Create view directory.
			FileSystem::makeDirectory($this->mainPath . '/resources/views/modules/user-meta-boxes/' . $idSlugify);

			// Create main view file.
			$view = FileSystem::get(__DIR__ . '/../Views/UserMetaBoxes'.$nonBladeSep.'/main.view');
			$view = str_replace('{{ className }}', $idSlugify, $view);
			$view = str_replace('{{ id }}', $id, $view);
			$view = str_replace('{{ id_slugify }}', $idSlugify, $view);
			FileSystem::put($this->mainPath . '/resources/views/modules/user-meta-boxes/' . $idSlugify . '/main' . $bladeExt, $view);

			// Create "Tab 1" view file.
			$view = FileSystem::get(__DIR__ . '/../Views/UserMetaBoxes'.$nonBladeSep.'/tab-1.view');
			$view = str_replace('{{ className }}', $idSlugify, $view);
			$view = str_replace('{{ id }}', $id, $view);
			$view = str_replace('{{ id_slugify }}', $idSlugify, $view);
			FileSystem::put($this->mainPath . '/resources/views/modules/user-meta-boxes/' . $idSlugify . '/tab-1' . $bladeExt, $view);

			// Create "Tab 2" view file.
			$view = FileSystem::get(__DIR__ . '/../Views/UserMetaBoxes'.$nonBladeSep.'/tab-2.view');
			$view = str_replace('{{ className }}', $idSlugify, $view);
			$view = str_replace('{{ id }}', $id, $view);
			$view = str_replace('{{ id_slugify }}', $idSlugify, $view);
			FileSystem::put($this->mainPath . '/resources/views/modules/user-meta-boxes/' . $idSlugify . '/tab-2' . $bladeExt, $view);

			// Create navigation view file.
			$view = FileSystem::get(__DIR__ . '/../Views/UserMetaBoxes'.$nonBladeSep.'/navigation.view');
			$view = str_replace('{{ className }}', $idSlugify, $view);
			$view = str_replace('{{ id }}', $id, $view);
			$view = str_replace('{{ id_slugify }}', $idSlugify, $view);
			FileSystem::put($this->mainPath . '/resources/views/modules/user-meta-boxes/' . $idSlugify . '/navigation' . $bladeExt, $view);
		}

		// Prepare new line for find function.
		$func = FileSystem::get(__DIR__ . '/../Funcs/UserMetaBoxes/user-meta-box.func');
		$func = str_replace('{{ id }}', $id, $func);
		$func = str_replace('{{ id_slugify }}', $idSlugify, $func);

		// Prepare new line for use class.
		$use = FileSystem::get(__DIR__ . '/../Uses/UserMetaBoxes/user-meta-box.use');
		$use = str_replace('{{ id }}', $id, $use);
		$use = str_replace('{{ id_slugify }}', $idSlugify, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('UserMetaBoxes', 'user_meta_boxes', $func, $use);

		// Output message.
		$this->writeln($output, '<green>Created new user meta box: "' . $id . '"</green>');

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