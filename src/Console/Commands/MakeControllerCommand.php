<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeControllerCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:controller')
			->setDescription('Create a new controller.                  | Eg: bin/wpsp make:controller MyController')
			->setHelp('This command allows you to create a controller.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the controller.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the controller: ');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$this->writeln($output, 'Missing name for the controller. Please try again.');
				return Command::INVALID;
			}
		}

		$this->validateClassName($output, $name);

		$path = $this->mainPath . '/app/Http/Controllers/' . $name . '.php';
		if (FileSystem::exists($path)) {
			$this->writeln($output, '[ERROR] Controller: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		$stub = FileSystem::get(__DIR__ . '/../Stubs/Controllers/controller.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);
		FileSystem::put($path, $stub);

		$this->writeln($output, '<green>Created new controller: "' . $name . '"</green>');

		return Command::SUCCESS;
	}

}