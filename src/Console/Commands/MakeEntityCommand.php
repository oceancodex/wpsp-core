<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\Filesystem\Filesystem;
use WPSPCORE\Traits\CommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class MakeEntityCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:entity')
			->setDescription('Create a new entity.              | Eg: bin/console make:entity MyEntity --table=custom_table --model=MyModel')
			->setHelp('This command allows you to create a entity.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the entity.')
			->addOption('table', 'table', InputOption::VALUE_OPTIONAL, 'The database table of the entity.')
			->addOption('model', 'model', InputOption::VALUE_OPTIONAL, 'The model of the entity.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name  = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the entity: ', 'MyEntity');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$output->writeln('Missing name for the entity. Please try again.');
				return Command::INVALID;
			}

			$tableQuestion = new Question('Please enter the database table of the entity: ', 'custom_table');
			$table         = $helper->ask($input, $output, $tableQuestion);

			$modelQuestion = new Question('Please enter the model of the entity: ', 'MyModel');
			$model         = $helper->ask($input, $output, $modelQuestion);
		}

		$table = $table ?? $input->getOption('table') ?: '';
		$model = $model ?? $input->getOption('model') ?: '';

		$this->validateClassName($output, $name);

		// Create class file.
		$content = Filesystem::get(__DIR__ . '/../Stubs/Entities/entity.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = str_replace('{{ table }}', $table, $content);
		$content = str_replace('{{ model }}', $model, $content);
		$content = $this->replaceNamespaces($content);
		Filesystem::put($this->mainPath . '/app/Entities/' . $name . '.php', $content);

		// Create model.
		if ($model) {
			$this->validateClassName($output, $model);

			$modelStub = Filesystem::get(__DIR__ . '/../Stubs/Models/model.stub');
			$modelStub = str_replace('{{ className }}', $model, $modelStub);
			$modelStub = str_replace('{{ table }}', $table, $modelStub);
			$modelStub = $this->replaceNamespaces($modelStub);
			Filesystem::put($this->mainPath . '/app/Models/' . $model . '.php', $modelStub);
		}

		// Output message.
		$output->writeln('Created new entity: "' . $name . '"');

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