<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\Objects\File\FileHandler;
use WPSPCORE\Objects\Slugify\Slugify;
use WPSPCORE\Traits\CommandsTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class MakeMetaBoxCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:meta-box')
			->setDescription('Create a new meta box.            | Eg: bin/console make:meta-box custom_meta_box --create-view')
			->setHelp('This command allows you to create a meta box.')
			->addArgument('id', InputArgument::OPTIONAL, 'The id of the meta box.')
			->addOption('create-view', 'create-view', InputOption::VALUE_NONE, 'Whether to create the view file for this meta box or not.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$id        = $input->getArgument('id');

		$helper = $this->getHelper('question');
		if (!$id) {
			$idQuestion = new Question('Please enter the ID of the meta box: ', 'custom_meta_box');
			$id         = $helper->ask($input, $output, $idQuestion);

			if (empty($id)) {
				$output->writeln('Missing ID for the meta box. Please try again.');
				return Command::INVALID;
			}

			$createViewQuestion = new ConfirmationQuestion('Do you want to create a view for this meta box? [y/N]: ', false);
			$createView = $helper->ask($input, $output, $createViewQuestion);
		}
		$idSlugify = Slugify::slugUnify($id, '_');
		$createView = $createView ?? $input->getOption('create-view');

		// Check exist.
		$exist = FileHandler::getFileSystem()->exists(__DIR__ . '/../../Extend/Components/MetaBoxes/' . $idSlugify . '.php');
//		$exist = $exist || FileHandler::getFileSystem()->exists(__DIR__ . '/../../../resources/views/modules/web/meta-boxes/'. $id . '.blade.php');
		if ($exist) {
			$output->writeln('[ERROR] Meta box: "' . $id . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		if ($createView) {
			// Create a view file.
			$view = FileHandler::getFileSystem()->get(__DIR__ . '/../Views/MetaBoxes/metabox.view');
			$view = str_replace('{{ id }}', $id, $view);
			$view = str_replace('{{ id_slugify }}', $idSlugify, $view);
			FileHandler::saveFile($view, __DIR__ . '/../../../resources/views/modules/web/meta-boxes/'. $id. '.blade.php');
			$content = FileHandler::getFileSystem()->get(__DIR__ . '/../Stubs/MetaBoxes/metabox-view.stub');
		}
		else {
			$content = FileHandler::getFileSystem()->get(__DIR__ . '/../Stubs/MetaBoxes/metabox.stub');
		}

		// Create class file.
		$content = str_replace('{{ className }}', $idSlugify, $content);
		$content = str_replace('{{ id }}', $id, $content);
		$content = str_replace('{{ id_slugify }}', $idSlugify, $content);
		$content = $this->replaceRootNamespace($content);
		FileHandler::saveFile($content, __DIR__ . '/../../Extend/Components/MetaBoxes/' . $idSlugify . '.php');

		// Prepare new line for find function.
		$func = FileHandler::getFileSystem()->get(__DIR__ . '/../Funcs/MetaBoxes/metabox.func');
		$func = str_replace('{{ id }}', $id, $func);
		$func = str_replace('{{ id_slugify }}', $idSlugify, $func);

		// Prepare new line for use class.
		$use = FileHandler::getFileSystem()->get(__DIR__ . '/../Uses/MetaBoxes/metabox.use');
		$use = str_replace('{{ id }}', $id, $use);
		$use = str_replace('{{ id_slugify }}', $idSlugify, $use);
		$use = $this->replaceRootNamespace($use);

		// Add class to route.
		$this->addClassToWebRoute('meta_boxes', $func, $use);

		// Output message.
		$output->writeln('Created new meta box: ' . $id);

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