<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeSeederCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:seeder')
			->setDescription('Create a new seeder.                      | Eg: bin/wpsp make:seeder MySeeder')
			->setHelp('This command allows you to create a seeder.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the seeder.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		// Validate class name.
		$this->validateClassName($output, $name);

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the seeder: ');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$this->writeln($output, 'Missing name for the seeder. Please try again.');
				return Command::INVALID;
			}
		}

		// Validate class name.
		$this->validateClassName($output, $name);

		// Check exist.
		$path = $this->mainPath . '/database/seeders/' . $name . '.php';
		if (FileSystem::exists($path)) {
			$this->writeln($output, '[ERROR] Seeder: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		$content = FileSystem::get(__DIR__ . '/../Stubs/Seeders/seeder.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = $this->replaceNamespaces($content);
		FileSystem::put($path, $content);

		// Append new seeder to DatabaseSeeder.
		$databaseSeederContent = FileSystem::get($this->mainPath . '/database/seeders/DatabaseSeeder.php');
		if (!preg_match('/\W' . $name . '::class/iu', $databaseSeederContent)) {
			$databaseSeederContent = preg_replace('/->call\(\[([\S\s]*?)]\);/iu', "->call([$1	" . $name . "::class,\n			]);", $databaseSeederContent);
			FileSystem::put($this->mainPath . '/database/seeders/DatabaseSeeder.php', $databaseSeederContent);
		}

		// Output message.
		$this->writeln($output, '<green>Created new seeder: "' . $name . '"</green>');

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