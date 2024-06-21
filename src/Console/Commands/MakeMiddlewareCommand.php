<?php

namespace OCBPCORE\Console\Commands;

use OCBPCORE\Objects\File\FileHandler;
use OCBPCORE\Traits\CommandsTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class MakeMiddlewareCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:middleware')
			->setDescription('Create a new middleware.          | Eg: bin/console make:middleware MyMiddleware')
			->setHelp('This command allows you to create a middleware...')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the middleware.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the middleware: ', 'MyMiddleware');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$output->writeln('Missing name for the middleware. Please try again.');
				return Command::INVALID;
			}
		}

		$this->validateClassName($output, $name);

		// Check exist.
		$exist = FileHandler::getFileSystem()->exists(__DIR__ . '/../../Http/Middleware/' . $name . '.php');
		if ($exist) {
			$output->writeln('[ERROR] Middleware: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		$content = FileHandler::getFileSystem()->get(__DIR__ . '/../Stubs/Middleware/middleware.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = $this->replaceRootNamespace($content);
		FileHandler::saveFile($content, __DIR__ . '/../../Http/Middleware/'. $name . '.php');

		// Output message.
		$output->writeln('Created new middleware: ' . $name);

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