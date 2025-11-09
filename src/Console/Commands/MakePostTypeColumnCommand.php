<?php

namespace WPSPCORE\Console\Commands;

use Illuminate\Support\Str;
use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakePostTypeColumnCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:post-type-column')
			->setDescription('Create a new post type column.            | Eg: bin/wpsp make:post-type-column my_custom_column')
			->setHelp('This command allows you to create a custom column for post type list table.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the post type column.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the post type column: ');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$this->writeln($output, 'Missing name for the post type column. Please try again.');
				return Command::INVALID;
			}
		}


		// Define variables.
		$nameSlugify = Str::slug($name, '_');

		// Validate class name.
		$this->validateClassName($output, $name);

		// Check exist.
		$path = $this->mainPath . '/app/Components/PostTypeColumns/' . $name . '.php';
		if (FileSystem::exists($path)) {
			$this->writeln($output, '[ERROR] Post type column: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		$stub = FileSystem::get(__DIR__ . '/../Stubs/PostTypeColumns/post_type_column.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);
		FileSystem::put($path, $stub);

		// Prepare new line for find function.
		$func = FileSystem::get(__DIR__ . '/../Funcs/PostTypeColumns/post_type_column.func');
		$func = str_replace('{{ name }}', $name, $func);
		$func = str_replace('{{ name_slugify }}', $nameSlugify, $func);

		// Prepare new line for use class.
		$use = FileSystem::get(__DIR__ . '/../Uses/PostTypeColumns/post_type_column.use');
		$use = str_replace('{{ name }}', $name, $use);
		$use = str_replace('{{ name_slugify }}', $nameSlugify, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('PostTypeColumns', 'post_type_columns', $func, $use);

		// Output message.
		$this->writeln($output, '<green>Created new post type column: "' . $name . '"</green>');

		return Command::SUCCESS;
	}

}