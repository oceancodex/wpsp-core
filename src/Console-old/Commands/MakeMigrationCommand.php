<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeMigrationCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:migration')
			->setDescription('Create a new migration.                   | Eg: bin/wpsp make:migration custom_migration')
			->setHelp('This command allows you to create a migration.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the migration.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name   = $input->getArgument('name');
		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the migration: ');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$this->writeln($output, 'Missing name for the migration. Please try again.');
				return Command::INVALID;
			}
		}

		// Define variables.
		$nameSlugify = Str::slug($name, '_');
		$date        = date('YmdHis');
		$nameSlugify = 'Version' . $date . '_' . $nameSlugify;

		// Create class file.
		$content = FileSystem::get(__DIR__ . '/../Stubs/Migrations/migration.stub');
		$content = str_replace('{{ className }}', $nameSlugify, $content);
		$content = str_replace('{{ dbTablePrefix }}', $this->funcs->_getDBTablePrefix(), $content);
		$content = str_replace('{{ dbCustomMigrationTablePrefix }}', $this->funcs->_getDBCustomMigrationTablePrefix(), $content);
		$content = $this->replaceNamespaces($content);
		FileSystem::put($this->mainPath . '/database/migrations/' . $nameSlugify . '.php', $content);

		// Output message.
		$this->writeln($output, '<green>Created new migration: "' . $nameSlugify . '"</green>');

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