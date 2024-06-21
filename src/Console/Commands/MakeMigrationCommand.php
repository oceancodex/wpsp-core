<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\Objects\File\FileHandler;
use WPSPCORE\Objects\Slugify\Slugify;
use WPSPCORE\Traits\CommandsTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class MakeMigrationCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:migration')
			->setDescription('Create a new migration.           | Eg: bin/console make:migration custom_migration')
			->setHelp('This command allows you to create a migration.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the migration.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name        = $input->getArgument('name');
		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the migration: ', 'custom_migration');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$output->writeln('Missing name for the migration. Please try again.');
				return Command::INVALID;
			}
		}

		// Define variables.
		$nameSlugify = Slugify::slugUnify($name, '_');
		$date        = date('YmdHis');
		$nameSlugify = 'Version' . $date . '_' . $nameSlugify;

		// Create class file.
		$content = FileHandler::getFileSystem()->get(__DIR__ . '/../Stubs/Migrations/migration.stub');
		$content = str_replace('{{ className }}', $nameSlugify, $content);
		$content = str_replace('{{ dbTablePrefix }}', _dbTablePrefix(), $content);
		$content = str_replace('{{ dbCMTablePrefix }}', _dbCMTablePrefix(), $content);
		$content = $this->replaceRootNamespace($content);
		FileHandler::saveFile($content, __DIR__ . '/../../../database/migrations/' . $nameSlugify . '.php');

		// Output message.
		$output->writeln('Created new migration: ' . $nameSlugify);

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