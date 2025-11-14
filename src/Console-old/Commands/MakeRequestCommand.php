<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeRequestCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:request')
			->setDescription('Create a new request.                     | Eg: bin/wpsp make:request UsersCreateRequest')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the request.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$q    = new Question('Please enter the name of the request: ');
			$name = $helper->ask($input, $output, $q);
			if (empty($name)) {
				$this->writeln($output, 'Missing name for the request. Please try again.');
				return Command::INVALID;
			}
		}

		$this->validateClassName($output, $name);

		$path = $this->mainPath . '/app/Http/Requests/' . $name . '.php';
		if (FileSystem::exists($path)) {
			$this->writeln($output, '[ERROR] Request: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		$stub = FileSystem::get(__DIR__ . '/../Stubs/Requests/request.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);
		FileSystem::put($path, $stub);

		$this->writeln($output, '<green>Created new request: "' . $name . '"</green>');

		return Command::SUCCESS;
	}

}