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

class MakeCommandCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:command')
			->setDescription('Create a new command.                     | Eg: bin/wpsp make:command custom:new-custom-command NewCustomCommand')
			->addArgument('cmd', InputArgument::OPTIONAL, 'The command.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the command.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$command = $input->getArgument('cmd');

		$helper = $this->getHelper('question');
		if (!$command) {
			$q       = new Question('Please enter the command: ');
			$command = $helper->ask($input, $output, $q);
			if (empty($command)) {
				$this->writeln($output, 'Missing command. Please try again.');
				return Command::INVALID;
			}

			$nameQuestion = new Question('Please enter the name of the command: ');
			$name         = $helper->ask($input, $output, $nameQuestion);
		}

		$commandSlugify = str_replace(' ', '-', strtolower($command));
		$name           = $name ?? $input->getArgument('name') ?: '';
		$this->validateClassName($output, $name);

		$path = $this->mainPath . '/app/Console/Commands/' . $name . '.php';
		if (FileSystem::exists($path)) {
			$this->writeln($output, '[ERROR] Listener: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		$stub = FileSystem::get(__DIR__ . '/../Stubs/Commands/command.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = str_replace('{{ command_slugify }}', $commandSlugify, $stub);
		$stub = $this->replaceNamespaces($stub);
		FileSystem::put($path, $stub);

		$func = FileSystem::get(__DIR__ . '/../Funcs/Commands/command.func');
		$func = str_replace('{{ className }}', $name, $func);
		$configFile = FileSystem::get($this->mainPath . '/config/commands.php');
		$configFile = str_replace('return [', "return [\n" . $func, $configFile);
		FileSystem::put($this->mainPath . '/config/commands.php', $configFile);

		$this->writeln($output, '<green>Created new command: "' . $commandSlugify . '" in "' . $name . '"</green>');

		return Command::SUCCESS;
	}

}