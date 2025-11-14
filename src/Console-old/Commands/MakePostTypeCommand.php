<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakePostTypeCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:post-type')
			->setDescription('Create a new post type.                   | Eg: bin/wpsp make:post-type custom_post_type')
			->setHelp('This command allows you to create a post type...')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the post type.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the post type: ');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$this->writeln($output, 'Missing name for the post type. Please try again.');
				return Command::INVALID;
			}
		}

		// Define variables.
		$nameSlugify = Str::slug($name, '_');

		// Check exist.
		$exist = FileSystem::exists($this->mainPath . '/app/Components/PostTypes/' . $nameSlugify . '.php');
		if ($exist) {
			$this->writeln($output, '[ERROR] Post type: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		$content = FileSystem::get(__DIR__ . '/../Stubs/PostTypes/posttype.stub');
		$content = str_replace('{{ className }}', $nameSlugify, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = str_replace('{{ name_slugify }}', $nameSlugify, $content);
		$content = $this->replaceNamespaces($content);
		FileSystem::put($this->mainPath . '/app/Components/PostTypes/' . $nameSlugify . '.php', $content);

		// Prepare new line for find function.
		$func = FileSystem::get(__DIR__ . '/../Funcs/PostTypes/posttype.func');
		$func = str_replace('{{ name }}', $name, $func);
		$func = str_replace('{{ name_slugify }}', $nameSlugify, $func);

		// Prepare new line for use class.
		$use = FileSystem::get(__DIR__ . '/../Uses/PostTypes/posttype.use');
		$use = str_replace('{{ name }}', $name, $use);
		$use = str_replace('{{ name_slugify }}', $nameSlugify, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('PostTypes', 'post_types', $func, $use);

		// Output message.
		$this->writeln($output, '<green>Created new post type: "' . $name . '"</green>');

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