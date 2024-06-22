<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\Objects\File\FileHandler;
use WPSPCORE\Traits\CommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeModelCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:model')
			->setDescription('Create a new model.               | Eg: bin/console make:model MyModel --table=custom_table --entity=MyEntity')
			->setHelp('This command allows you to create a model.')
			->addArgument('name', InputArgument::OPTIONAL, 'The class name of the model.')
			->addOption('table', 'table', InputOption::VALUE_OPTIONAL, 'The table of the model.')
			->addOption('entity', 'entity', InputOption::VALUE_NONE, 'The entity of the model.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name   = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the model: ', 'MyModel');
			$name = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$output->writeln('Missing name for the model. Please try again.');
				return Command::INVALID;
			}

			$tableQuestion = new Question('Please enter the table name of the model: ', 'my_model_table');
			$table = $helper->ask($input, $output, $tableQuestion);

			$entityQuestion = new ConfirmationQuestion("Do you want to create entity? [y/N]: ", false);
			$entity = $helper->ask($input, $output, $entityQuestion);

			if ($entity) {
				$entityQuestion = new Question("Please enter the entity name: ", 'MyEntity');
				$entity = $helper->ask($input, $output, $entityQuestion);
			}
		}

		$this->validateClassName($output, $name);

		// Check exist.
		$exist = FileHandler::getFileSystem()->exists(_wpspPath() . '/app/Models/' . $name . '.php');
		if ($exist) {
			$output->writeln('[ERROR] Model: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		$table  = $table ?? $input->getOption('table') ?: '';
		$entity = $entity ?? $input->getOption('entity') ?: '';

		// Create class file.
		$content = FileHandler::getFileSystem()->get(__DIR__ . '/../Stubs/Models/model.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = str_replace('{{ table }}', $table ?? null, $content);
		$content = str_replace('{{ entity }}', $entity ?? null, $content);
		$content = $this->replaceNamespaces($content);
		FileHandler::saveFile($content, _wpspPath() . '/app/Models/'. $name . '.php');

		// Create entity.
		if ($entity) {
			$this->validateClassName($output, $entity);

			$entityStub = FileHandler::getFileSystem()->get(__DIR__ . '/../Stubs/Entities/entity.stub');
			$entityStub = str_replace('{{ className }}', $entity, $entityStub);
			$entityStub = str_replace('{{ table }}', $table, $entityStub);
			$entityStub = $this->replaceNamespaces($entityStub);
			FileHandler::saveFile($entityStub, _wpspPath() . '/app/Entities/' . $entity . '.php');
		}

		// Output message.
		$output->writeln('Created new model: ' . $name);

		// this method must return an integer number with the "exit status code"
		// of the command. You can also use these constants to make code more readable

		// return this if there was no problem running the command
		// (it's equivalent to returning int(0))
		return Command::SUCCESS;

		// or return this if some error happened during the execution
		// (it's equivalent to returning int(1))
		// return Command::FAILURE;

		// or return this to indicate incorrect command usage; e.g. invalid options
		// or missing arguments (it's equivalent to returning int(2))
		// return Command::INVALID
	}

}