<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeListenerCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:listener')
			->setDescription('Create a new listener.                    | Eg: bin/wpsp make:listener UserCreatedListener')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the listener.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$q    = new Question('Please enter the name of the listener: ');
			$name = $helper->ask($input, $output, $q);
			if (empty($name)) {
				$this->writeln($output, 'Missing name for the listener. Please try again.');
				return Command::INVALID;
			}
		}

		$this->validateClassName($output, $name);

		$path = $this->mainPath . '/app/Listeners/' . $name . '.php';
		if (FileSystem::exists($path)) {
			$this->writeln($output, '[ERROR] Listener: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		$stub = FileSystem::get(__DIR__ . '/../Stubs/Listeners/listener.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);
		FileSystem::put($path, $stub);

		$this->writeln($output, '<green>Created new listener: "' . $name . '"</green>');

		return Command::SUCCESS;
	}

}