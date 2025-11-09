<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use WPSPCORE\Console\Traits\CommandsTrait;
use WPSPCORE\Queue\QueueFactory;
use WPSPCORE\Queue\ExceptionHandler;
use WPSPCORE\Queue\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueWorkCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('queue:work')
			->setDescription('Start processing jobs on the queue as a daemon.')
			->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'The name of the queue connection to work', null)
			->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'The names of the queues to work', 'default')
			->addOption('once', null, InputOption::VALUE_NONE, 'Only process the next job on the queue')
			->addOption('stop-when-empty', null, InputOption::VALUE_NONE, 'Stop when the queue is empty')
			->addOption('delay', null, InputOption::VALUE_OPTIONAL, 'The number of seconds to delay failed jobs', 0)
			->addOption('memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128)
			->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'The number of seconds a child process can run', 60)
			->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3)
			->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 1)
			->addOption('max-jobs', null, InputOption::VALUE_OPTIONAL, 'The number of jobs to process before stopping', 0)
			->addOption('max-time', null, InputOption::VALUE_OPTIONAL, 'The maximum number of seconds the worker should run', 0)
			->addOption('force', null, InputOption::VALUE_NONE, 'Force the worker to run even in maintenance mode')
			->addOption('rest', null, InputOption::VALUE_OPTIONAL, 'Number of seconds to rest between jobs', 0);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		// Boot Eloquent.
		$this->funcs->getEloquent(true);

		// Boot Queue.
		$queue = $this->funcs->getQueue(true);
		$queue->setupQueue();

		if (!$queue) {
			$this->writeln($output, '<red>[X] Queue chưa được khởi tạo!</red>');
			return Command::FAILURE;
		}

		$connection = $input->getOption('connection') ?: $this->funcs->_config('queue.default', 'sync');
		$queueName  = $input->getOption('queue');

		$this->writeln($output, '<green>[✓] Queue đã được kích hoạt!</green>');
		$this->writeln($output, '<yellow>[-] Kết nối: ' . $connection . '</yellow>');
		$this->writeln($output, '<yellow>[-] Hàng đợi (queue): ' . $queueName . '</yellow>');
		$this->writeln($output, '<yellow>[-] Ghi nhật ký: ' . Logger::getLogFile() . '</yellow>');

		$container = $queue->getContainer();
		$manager   = $queue->getManager();

		$exceptionHandler = new ExceptionHandler(
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv(),
			[
				'funcs' => $this->funcs,
			]
		);

		$factory = new QueueFactory($manager);

		$failedJobProvider = null;
		if ($container->has(FailedJobProviderInterface::class)) {
			$failedJobProvider = $container->make(FailedJobProviderInterface::class);
		}

		$worker = new Worker(
			$factory,
			$container->make('events'),
			$exceptionHandler,
			function() {
				return false;
			}
		);

		$options = new WorkerOptions(
			$input->getOption('delay'),
			$input->getOption('memory'),
			$input->getOption('timeout'),
			$input->getOption('sleep'),
			$input->getOption('tries'),
			$input->getOption('force'),
			$input->getOption('stop-when-empty'),
			$input->getOption('max-jobs'),
			$input->getOption('max-time'),
			$input->getOption('rest')
		);

		if ($input->getOption('once')) {
			$this->writeln($output, '<yellow>[-] Đang xử lý job...</yellow>');
			$worker->runNextJob($connection, $queueName, $options);
		}
		else {
			$this->writeln($output, '<green>[✓] Đang lắng nghe...</green>');
			$worker->daemon($connection, $queueName, $options);
		}

		$this->writeln($output, '<red>[-] Queue đã dừng!</red>');

		return Command::SUCCESS;
	}

}