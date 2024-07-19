<?php

namespace WPSPCORE\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\Traits\CommandsTrait;

class MigrationMigrateCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('migration:migrate')
			->setDescription('Migration migrate.')
			->setHelp('This command allows you to run migration migrate.')
			->addOption('seed', 'seed', InputOption::VALUE_NONE, 'Run seeders or not?.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		exec('php bin/migrations migrate -n', $execOutput, $exitCode);

		foreach ($execOutput as $execOutputKey => $execOutputItem) {
			if (empty($execOutputItem)) {
				unset($execOutput[$execOutputKey]);
			}
		}

		foreach ($execOutput as $execOutputItem) {
			$execOutputItem = trim($execOutputItem);
			if (preg_match('/\[OK|\[Success/iu', $execOutputItem)) {
				$output->writeln('<fg=green>'. $execOutputItem . '  </>');
			}
			else {
				$output->writeln($execOutputItem);
			}
		}

		$seed = $input->getOption('seed');
		if ($seed) {
			$namespace = $this->funcs->_getRootNamespace();
			$databaseSeeder = $namespace. '\\database\\seeders\\DatabaseSeeder';
			(new $databaseSeeder($output))->run();
		}

		// Output message.
//		$output->writeln('Migrated.');

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