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

class MakeTemplateCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:template')
			->setDescription('Create a new page template.       | Eg: bin/console make:template custom_template')
			->setHelp('This command allows you to create a page template.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the template.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the template: ', 'custom_template');
			$name = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$output->writeln('Missing name for the template. Please try again.');
				return Command::INVALID;
			}
		}

		// Define variables.
		$nameSlugify = Slugify::slugUnify($name, '_');

		// Check exist.
		$exist = FileHandler::getFileSystem()->exists(_wpspPath() . '/app/Components/Templates/' . $nameSlugify . '.php');
		if ($exist) {
			$output->writeln('[ERROR] Template: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		$content = FileHandler::getFileSystem()->get(__DIR__ . '/../Stubs/Templates/template.stub');
		$content = str_replace('{{ className }}', $nameSlugify, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = $this->replaceNamespaces($content);
		FileHandler::saveFile($content, _wpspPath() . '/app/Components/Templates/'. $nameSlugify. '.php');

		// Create a view file.
		$view = FileHandler::getFileSystem()->get(__DIR__ . '/../Views/Templates/template.view');
		$view = str_replace('{{ name }}', $name, $view);
		$view = str_replace('{{ name_slugify }}', $nameSlugify, $view);
		FileHandler::saveFile($view, _wpspPath() . '/resources/views/modules/web/templates/'. $name. '.php');

		// Prepare new line for find function.
		$func = FileHandler::getFileSystem()->get(__DIR__ . '/../Funcs/Templates/template.func');
		$func = str_replace('{{ name }}', $name, $func);
		$func = str_replace('{{ name_slugify }}', $nameSlugify, $func);

		// Prepare new line for use class.
		$use = FileHandler::getFileSystem()->get(__DIR__ . '/../Uses/Templates/template.use');
		$use = str_replace('{{ name }}', $name, $use);
		$use = str_replace('{{ name_slugify }}', $nameSlugify, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToWebRoute('templates', $func, $use);

		// Output message.
		$output->writeln('Created new page template: ' . $name);

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