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

class MakeScheduleCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:schedule')
			->setDescription('Create a new schedule.                    | Eg: bin/wpsp make:schedule custom_schedule_hook hourly')
			->setHelp('This command allows you to create a schedule.')
			->addArgument('hook', InputArgument::OPTIONAL, 'The hook of the schedule.')
			->addArgument('interval', InputArgument::OPTIONAL, 'The interval of the schedule.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$hook     = $input->getArgument('hook');
		$interval = $input->getArgument('interval');

		$helper = $this->getHelper('question');
		if (!$hook) {
			$hookQuestion = new Question('Please enter the hook of the schedule: ');
			$hook         = $helper->ask($input, $output, $hookQuestion);

			if (empty($hook)) {
				$this->writeln($output, 'Missing hook for the schedule. Please try again.');
				return Command::INVALID;
			}
		}

		if (!$interval) {
			$intervalQuestion = new Question('Please enter the interval of the schedule (Leave empty = hourly): ');
			$interval         = $helper->ask($input, $output, $intervalQuestion);
		}
		$interval = empty($interval) ? 'hourly' : $interval;

		// Define variables.
		$hookSlugify = Str::slug($hook, '_');
		$intervalSlugify = Str::slug($interval, '_');

		// Check exist.
		$exist = FileSystem::exists($this->mainPath . '/app/Components/Schedules/' . $hookSlugify . '.php');
		if ($exist) {
			$this->writeln($output, '[ERROR] Shortcode: "' . $hookSlugify . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		$content = FileSystem::get(__DIR__ . '/../Stubs/Schedules/schedule.stub');

		// Create class file.
		$content = str_replace('{{ className }}', $hookSlugify, $content);
		$content = str_replace('{{ hook }}', $hook, $content);
		$content = str_replace('{{ hook_slugify }}', $hookSlugify, $content);
		$content = str_replace('{{ interval }}', $interval, $content);
		$content = str_replace('{{ interval_slugify }}', $intervalSlugify, $content);
		$content = $this->replaceNamespaces($content);
		FileSystem::put($this->mainPath . '/app/Components/Schedules/' . $hookSlugify . '.php', $content);

		// Prepare new line for find function.
		$func = FileSystem::get(__DIR__ . '/../Funcs/Schedules/schedule.func');
		$func = str_replace('{{ hook }}', $hook, $func);
		$func = str_replace('{{ hook_slugify }}', $hookSlugify, $func);
		$func = str_replace('{{ interval }}', $interval, $func);
		$func = str_replace('{{ interval_slugify }}', $intervalSlugify, $func);

		// Prepare new line for use class.
		$use = FileSystem::get(__DIR__ . '/../Uses/Schedules/schedule.use');
		$use = str_replace('{{ hook }}', $hook, $use);
		$use = str_replace('{{ hook_slugify }}', $hookSlugify, $use);
		$use = str_replace('{{ interval }}', $interval, $use);
		$use = str_replace('{{ interval_slugify }}', $intervalSlugify, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('Schedules', 'schedules', $func, $use);

		// Output message.
		$this->writeln($output, '<green>Created new schedule: "' . $hook . '"</green>');

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