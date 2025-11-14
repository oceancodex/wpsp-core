<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\Console\Traits\CommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueFailedCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('queue:failed')
			->setDescription('List all of the failed queue jobs.')
			->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Number of jobs to show', 10);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$eloquent = $this->funcs->getEloquent(true);
		if (!$eloquent) {
			$this->writeln($output, '<error>Eloquent not initialized</error>');
			return Command::FAILURE;
		}

		$limit     = (int)$input->getOption('limit');
		$tableName = 'cm_failed_jobs';

		try {
			$db = $eloquent->getCapsule()->getDatabaseManager();

			$failedJobs = $db->table($tableName)
				->orderByDesc('failed_at')
				->limit($limit)
				->get();

			if ($failedJobs->isEmpty()) {
				$this->writeln($output, '<info>No failed jobs found.</info>');
				return Command::SUCCESS;
			}

			$this->writeln($output, '<info>Failed Jobs (Last ' . $limit . '):</info>');
			$this->writeln($output, '');

			foreach ($failedJobs as $job) {
				$this->writeln($output, '<fg=red>ID:</> ' . $job->id);
				$this->writeln($output, '<fg=red>UUID:</> ' . $job->uuid);
				$this->writeln($output, '<fg=red>Queue:</> ' . $job->queue);
				$this->writeln($output, '<fg=red>Failed At:</> ' . $job->failed_at);
				$this->writeln($output, '<fg=red>Exception:</> ' . substr($job->exception, 0, 200) . '...');
				$this->writeln($output, '');
			}

			return Command::SUCCESS;
		}
		catch (\Throwable $e) {
			$this->writeln($output, '<error>Error: ' . $e->getMessage() . '</error>');
			return Command::FAILURE;
		}
	}

}