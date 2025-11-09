<?php

namespace WPSPCORE\Console\Commands;

use Symfony\Component\Console\Input\InputOption;
use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeExceptionCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:exception')
			->setDescription('Create a new exception.                   | Eg: bin/wpsp make:exception MyCustomException')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the exception.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		// If name is empty, ask questions.
		$helper = $this->getHelper('question');
		if (!$name) {
			$q       = new Question('Please enter the name of the exception: ');
			$name = $helper->ask($input, $output, $q);
			if (empty($name)) {
				$this->writeln($output, 'Missing name of the exception. Please try again.');
				return Command::INVALID;
			}
		}

		// Validate class name.
		$this->validateClassName($output, $name);

		// Prepare class path.
		$path = $this->mainPath . '/app/Exceptions/' . $name . '.php';

		// Check exist.
		if (FileSystem::exists($path)) {
			$this->writeln($output, '[ERROR] Exception: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		$stub = FileSystem::get(__DIR__ . '/../Stubs/Exceptions/exception.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);
		FileSystem::put($path, $stub);

		// Output message.
		$this->writeln($output, '<green>Created new exception: "' . $name . '"</green>');

		return Command::SUCCESS;
	}

}