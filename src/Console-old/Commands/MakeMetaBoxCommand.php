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

class MakeMetaBoxCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:meta-box')
			->setDescription('Create a new meta box.                    | Eg: bin/wpsp make:meta-box custom_meta_box --create-view')
			->setHelp('This command allows you to create a meta box.')
			->addArgument('id', InputArgument::OPTIONAL, 'The id of the meta box.')
			->addOption('create-view', 'create-view', InputOption::VALUE_NONE, 'Create view files for this meta box or not?');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$id = $input->getArgument('id');

		$helper = $this->getHelper('question');
		if (!$id) {
			$idQuestion = new Question('Please enter the ID of the meta box: ');
			$id         = $helper->ask($input, $output, $idQuestion);

			if (empty($id)) {
				$this->writeln($output, 'Missing ID for the meta box. Please try again.');
				return Command::INVALID;
			}

			$createViewQuestion = new ConfirmationQuestion('Do you want to create view files for this meta box? [y/N]: ', false);
			$createView         = $helper->ask($input, $output, $createViewQuestion);
		}
		$idSlugify  = Str::slug($id, '_');
		$createView = $createView ?? $input->getOption('create-view');

		// Check exist.
		$exist = FileSystem::exists($this->mainPath . '/app/Components/MetaBoxes/' . $idSlugify . '.php');
//		$exist = $exist || FileSystem::exists(__DIR__ . '/../../../resources/views/modules/meta-boxes/'. $id . '.blade.php');
		if ($exist) {
			$this->writeln($output, '[ERROR] Meta box: "' . $id . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		if ($createView) {
			// Create a view file.
			$view = FileSystem::get(__DIR__ . '/../Views/MetaBoxes/meta-box.view');
			$view = str_replace('{{ id }}', $id, $view);
			$view = str_replace('{{ id_slugify }}', $idSlugify, $view);
			FileSystem::put($this->mainPath . '/resources/views/modules/meta-boxes/' . $id . '.blade.php', $view);
			$content = FileSystem::get(__DIR__ . '/../Stubs/MetaBoxes/meta-box-view.stub');
		}
		else {
			$content = FileSystem::get(__DIR__ . '/../Stubs/MetaBoxes/meta-box.stub');
		}

		// Create class file.
		$content = str_replace('{{ className }}', $idSlugify, $content);
		$content = str_replace('{{ id }}', $id, $content);
		$content = str_replace('{{ id_slugify }}', $idSlugify, $content);
		$content = $this->replaceNamespaces($content);
		FileSystem::put($this->mainPath . '/app/Components/MetaBoxes/' . $idSlugify . '.php', $content);

		// Prepare new line for find function.
		$func = FileSystem::get(__DIR__ . '/../Funcs/MetaBoxes/meta-box.func');
		$func = str_replace('{{ id }}', $id, $func);
		$func = str_replace('{{ id_slugify }}', $idSlugify, $func);

		// Prepare new line for use class.
		$use = FileSystem::get(__DIR__ . '/../Uses/MetaBoxes/meta-box.use');
		$use = str_replace('{{ id }}', $id, $use);
		$use = str_replace('{{ id_slugify }}', $idSlugify, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('MetaBoxes', 'meta_boxes', $func, $use);

		// Output message.
		$this->writeln($output, '<green>Created new meta box: "' . $id . '"</green>');

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