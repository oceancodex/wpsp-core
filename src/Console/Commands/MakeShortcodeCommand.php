<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Filesystem\Filesystem;
use WPSPCORE\Traits\CommandsTrait;

class MakeShortcodeCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:shortcode')
			->setDescription('Create a new shortcode.                   | Eg: bin/console make:shortcode custom_shortcode --create-view')
			->setHelp('This command allows you to create a shortcode.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the shortcode.')
			->addOption('create-view', 'create-view', InputOption::VALUE_NONE, 'Whether to create the view file for this shortcode or not.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the shortcode: ');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$output->writeln('Missing name for the shortcode. Please try again.');
				return Command::INVALID;
			}

			$createViewQuestion = new ConfirmationQuestion('Do you want to create a view for this shortcode? [y/N]: ', false);
			$createView = $helper->ask($input, $output, $createViewQuestion);
		}

		// Define variables.
		$nameSlugify = Str::slug($name, '_');
		$createView = $createView ?? $input->getOption('create-view');

		// Check exist.
		$exist = Filesystem::exists($this->mainPath . '/app/Extend/Components/Shortcodes/' . $nameSlugify . '.php');
		if ($exist) {
			$output->writeln('[ERROR] Shortcode: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		if ($createView) {
			// Create a view file.
			$view = Filesystem::get(__DIR__ . '/../Views/Shortcodes/shortcode.view');
			$view = str_replace('{{ name }}', $name, $view);
			$view = str_replace('{{ name_slugify }}', $nameSlugify, $view);
			Filesystem::put($this->mainPath . '/resources/views/modules/web/shortcodes/'. $name. '.blade.php', $view);
			$content = Filesystem::get(__DIR__ . '/../Stubs/Shortcodes/shortcode-view.stub');
		}
		else {
			$content = Filesystem::get(__DIR__ . '/../Stubs/Shortcodes/shortcode.stub');
		}

		// Create class file.
		$content = str_replace('{{ className }}', $nameSlugify, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = str_replace('{{ name_slugify }}', $nameSlugify, $content);
		$content = $this->replaceNamespaces($content);
		Filesystem::put($this->mainPath . '/app/Extend/Components/Shortcodes/'. $nameSlugify. '.php', $content);

		// Prepare new line for find function.
		$func = Filesystem::get(__DIR__ . '/../Funcs/Shortcodes/shortcode.func');
		$func = str_replace('{{ name }}', $name, $func);
		$func = str_replace('{{ name_slugify }}', $nameSlugify, $func);

		// Prepare new line for use class.
		$use = Filesystem::get(__DIR__ . '/../Uses/Shortcodes/shortcode.use');
		$use = str_replace('{{ name }}', $name, $use);
		$use = str_replace('{{ name_slugify }}', $nameSlugify, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToWebRoute('shortcodes', $func, $use);

		// Output message.
		$output->writeln('Created new shortcode: "' . $name . '"');

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