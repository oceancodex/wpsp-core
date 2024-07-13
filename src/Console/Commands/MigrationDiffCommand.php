<?php

namespace WPSPCORE\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\Traits\CommandsTrait;

class MigrationDiffCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('migration:diff')
			->setDescription('Migration diff.')
			->setHelp('This command allows you to run migration diff.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		$tablePrefix = $this->funcs->_getDBTablePrefix();

		exec('php bin/migrations diff --filter-expression="/^'.$tablePrefix.'((?!cm_))/iu" -n', $execOutput);
		foreach ($execOutput as $execOutputItem) {
			if ($execOutputItem) {
				$execOutputItem = trim($execOutputItem);
				if (preg_match('/generated/iu', $execOutputItem)) {
					$output->writeln('<fg=green>'. $execOutputItem . '  </>');
				}
				elseif (preg_match('/warning/iu', $execOutputItem)) {
					$output->writeln('<fg=yellow>'. $execOutputItem . '  </>');
				}
				else {
					$output->writeln('<fg=blue>'. $execOutputItem . '  </>');
				}
			}
		}

		// Output message.
//		$output->writeln('Diff.');

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