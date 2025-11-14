<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeAPICommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:api')
			->setDescription('Create a new API end point.               | Eg: bin/wpsp make:api my-api-endpoint')
			->setHelp('This command allows you to create an API end point.')
			->addArgument('path', InputArgument::OPTIONAL, 'The path of the API end point.')
			->addOption('method', 'method', InputOption::VALUE_OPTIONAL, 'The method of the API end point.')
			->addOption('namespace', 'namespace', InputOption::VALUE_OPTIONAL, 'The namespace of the API end point.')
			->addOption('ver', 'ver', InputOption::VALUE_OPTIONAL, 'The version of the API end point.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$path = $input->getArgument('path');

		// If path is empty.
		$helper = $this->getHelper('question');
		if (!$path) {
			$pathQuestion = new Question('Please enter the path of the API end point: ');
			$path         = $helper->ask($input, $output, $pathQuestion);

			$methodQuestion = new Question('Please enter the method of the API end point (blank is "get"): ');
			$method         = $helper->ask($input, $output, $methodQuestion);

			$namespaceQuestion = new Question('Please enter the namespace of the API end point (blank is "' . $this->funcs->_getAppShortName() . '"): ');
			$namespace         = $helper->ask($input, $output, $namespaceQuestion);

			$verQuestion = new Question('Please enter the ver of the API end point (blank is "v1"): ');
			$ver         = $helper->ask($input, $output, $verQuestion);

			if (empty($path)) {
				$this->writeln($output, 'Missing path for the the API end point. Please try again.');
				return Command::INVALID;
			}
		}

		// Define variables.
		$pathSlugify = Str::slug($path);
		$name        = $path;
		$nameSlugify = Str::slug($name, '_');

		$method = $method ?? $input->getOption('method') ?: '';
		$method = strtolower($method);

		$namespace = $namespace ?? $input->getOption('namespace') ?: '';
		if ($namespace) {
			$namespace = "'" . $namespace . "'";
		}
		else {
			$namespace = 'null';
		}

		$ver = $ver ?? $input->getOption('ver') ?: '';
		if ($ver) {
			$ver = "'" . $ver . "'";
		}
		else {
			$ver = 'null';
		}

		// Prepare new line for find function.
		$func = FileSystem::get(__DIR__ . '/../Funcs/APIs/api.func');
		$func = str_replace('{{ name }}', $name, $func);
		$func = str_replace('{{ name_slugify }}', $nameSlugify, $func);
		$func = str_replace('{{ path }}', $path, $func);
		$func = str_replace('{{ path_slugify }}', $pathSlugify, $func);
		$func = str_replace('{{ method }}', $method, $func);
		$func = str_replace('{{ namespace }}', $namespace, $func);
		$func = str_replace('{{ ver }}', $ver, $func);

		// Prepare new line for use class.
		$use = FileSystem::get(__DIR__ . '/../Uses/APIs/api.use');
		$use = str_replace('{{ name }}', $name, $use);
		$use = str_replace('{{ name_slugify }}', $nameSlugify, $use);
		$use = str_replace('{{ path }}', $path, $use);
		$use = str_replace('{{ path_slugify }}', $pathSlugify, $use);
		$use = str_replace('{{ method }}', $method, $use);
		$use = str_replace('{{ namespace }}', $namespace, $use);
		$use = str_replace('{{ ver }}', $ver, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('Apis', 'apis', $func, $use);

		// Output message.
		$this->writeln($output, '<green>Created new API end point: "' . $path . '"</green>');

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