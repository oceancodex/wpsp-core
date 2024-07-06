<?php

namespace WPSPCORE\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Filesystem\Filesystem;
use WPSPCORE\Traits\CommandsTrait;

class MakeSeederCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:seeder')
			->setDescription('Create a new seeder.                      | Eg: bin/console make:seeder MySeeder')
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
				$output->writeln('Missing name for the seeder. Please try again.');
				return Command::INVALID;
			}
		}

		// Create class file.
		$content = Filesystem::get(__DIR__ . '/../Stubs/Seeders/seeder.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = $this->replaceNamespaces($content);
		Filesystem::put($this->mainPath . '/database/seeders/' . $name . '.php', $content);

		// Append new seeder to DatabaseSeeder.
		$databaseSeederContent = Filesystem::get($this->mainPath . '/database/seeders/DatabaseSeeder.php');
		if (!preg_match('/\W'.$name.'::class/iu', $databaseSeederContent)) {
			$databaseSeederContent = preg_replace('/->call\(\[([\S\s]*?)]\);/iu', "->call([$1	".$name."::class,\n		]);", $databaseSeederContent);
			Filesystem::put($this->mainPath . '/database/seeders/DatabaseSeeder.php', $databaseSeederContent);
		}

		// Output message.
		$output->writeln('Created new seeder: "' . $name . '"');

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