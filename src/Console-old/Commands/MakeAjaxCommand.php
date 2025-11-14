<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeAjaxCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:ajax')
			->setDescription('Create a new Ajax action.                 | Eg: bin/wpsp make:ajax GET my_action --nopriv')
			->setHelp('This command allows you to create an Ajax action.')
			->addArgument('method', InputArgument::OPTIONAL, 'The method of the Ajax.')
			->addArgument('action', InputArgument::OPTIONAL, 'The action name of the Ajax.')
			->addOption('nopriv', 'nopriv', InputOption::VALUE_NONE, 'Fires non-authenticated Ajax actions for logged-out users.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$method = $input->getArgument('method');
		$action = $input->getArgument('action');

		$helper = $this->getHelper('question');
		if (!$action) {
			$actionQuestion = new Question('Please enter the action name of the ajax: ');
			$action         = $helper->ask($input, $output, $actionQuestion);

			if (empty($action)) {
				$this->writeln($output, 'Missing action name for the ajax. Please try again.');
				return Command::INVALID;
			}

			$noprivQuestion = new ConfirmationQuestion('Do you want to allow access for non-logged user? (nopriv) [y/N]: ');
			$nopriv         = $helper->ask($input, $output, $noprivQuestion);
		}

		// Define variables.
		$method        = strtolower($method);
		$actionSlugify = Str::slug($action, '_');
		$nopriv        = $nopriv ?? $input->getOption('nopriv') ? 'true' : 'false';

		// Prepare new line for find function.
		$func = FileSystem::get(__DIR__ . '/../Funcs/Ajaxs/ajax.func');
		$func = str_replace('{{ method }}', $method, $func);
		$func = str_replace('{{ action }}', $action, $func);
		$func = str_replace('{{ action_slugify }}', $actionSlugify, $func);
		$func = str_replace('{{ nopriv }}', $nopriv, $func);

		// Prepare new line for use class.
		$use = FileSystem::get(__DIR__ . '/../Uses/Ajaxs/ajax.use');
		$use = str_replace('{{ method }}', $method, $use);
		$use = str_replace('{{ action }}', $action, $use);
		$use = str_replace('{{ action_slugify }}', $actionSlugify, $use);
		$use = str_replace('{{ nopriv }}', $nopriv, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('Ajaxs', 'ajaxs', $func, $use);

		// Output message.
		$this->writeln($output, '<green>Created new Ajax action: "' . $action . '"</green>');

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