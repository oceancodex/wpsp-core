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

class MakeEventCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:event')
			->setDescription('Create a new event.                       | Eg: bin/wpsp make:event UsersCreatedEvent')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the event.')
			->addOption('listeners', 'listeners', InputOption::VALUE_OPTIONAL, 'The listeners of the event. (Eg: UserCreatedListener, UserDeletedListener)');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name      = $input->getArgument('name');
		$listeners = $input->getOption('listeners');

		$helper = $this->getHelper('question');
		if (!$name) {
			$q    = new Question('Please enter the name of the event: ');
			$name = $helper->ask($input, $output, $q);
			if (empty($name)) {
				$this->writeln($output, 'Missing name for the event. Please try again.');
				return Command::INVALID;
			}

			$listenersQuestion = new Question('Please enter the class name of listeners for this event (separate by comma): ');
			$listeners         = $helper->ask($input, $output, $listenersQuestion);
		}

		$this->validateClassName($output, $name);
		$listeners = preg_replace('/\s+/', '', $listeners);
		$listeners = $listeners ? explode(',', $listeners) : null;

		$path = $this->mainPath . '/app/Events/' . $name . '.php';
		if (FileSystem::exists($path)) {
			$this->writeln($output, '[ERROR] Event: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		$stub = FileSystem::get(__DIR__ . '/../Stubs/Events/event.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);
		FileSystem::put($path, $stub);

		// Prepare listeners HTML.
		$listenersHTML = '';
		if ($listeners) {
			foreach ($listeners as $key => $listener) {
				if ($key > 0) {
					$listenersHTML .= "		\{{ rootNamespace }}\app\Listeners\\$listener::class,\n";
				}
				else {
					$listenersHTML .= "\{{ rootNamespace }}\app\Listeners\\$listener::class,\n";
				}
			}
		}
		$listenersHTML = $this->replaceNamespaces($listenersHTML);

		$func       = FileSystem::get(__DIR__ . '/../Funcs/Events/event.func');
		$func       = str_replace('{{ className }}', $name, $func);
		$func       = str_replace('{{ listeners }}', $listenersHTML, $func);
		$func       = $this->replaceNamespaces($func);
		$configFile = FileSystem::get($this->mainPath . '/config/events.php');
		$configFile = str_replace('return [', "return [\n" . $func, $configFile);
		FileSystem::put($this->mainPath . '/config/events.php', $configFile);

		$this->writeln($output, '<green>Created new event: "' . $name . '"</green>');

		return Command::SUCCESS;
	}

}