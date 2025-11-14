<?php

namespace WPSPCORE\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\Console\Traits\CommandsTrait;

class MigrationDiffCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('migration:diff')
			->setDescription('Migration diff.')
			->setHelp('This command allows you to run migration diff.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$tablePrefix = $this->funcs->_getDBTablePrefix();

		exec('php bin/migrations diff --filter-expression="/^' . $tablePrefix . '((?!cm_))/iu" -n', $execOutput);

		foreach ($execOutput as $execOutputItem) {
			if ($execOutputItem) {
				$execOutputItem = trim($execOutputItem);
				if (preg_match('/generated/iu', $execOutputItem)) {
					$this->writeln($output, '<fg=green>' . $execOutputItem . '  </>');
				}
				elseif (preg_match('/warning/iu', $execOutputItem)) {
					$this->writeln($output, '<fg=yellow>' . $execOutputItem . '  </>');
				}
				else {
					$this->writeln($output, '<fg=blue>' . $execOutputItem . '  </>');
				}
			}
		}

		return Command::SUCCESS;
	}

}