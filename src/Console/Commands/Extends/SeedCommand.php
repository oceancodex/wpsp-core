<?php

namespace WPSPCORE\Console\Commands\Extends;

use Illuminate\Database\Console\Seeds\SeedCommand as Command;
use WPSPCORE\Console\Traits\CommandsTrait;

class SeedCommand extends Command {

	use CommandsTrait;

	/**
	 * Initializes the command after the input has been bound and before the input
	 * is validated.
	 *
	 * This is mainly useful when a lot of commands extends one main command
	 * where some things need to be initialized based on the input arguments and options.
	 */
	protected function initialize($input, $output): void {
		parent::initialize($input, $output);
		$this->funcs = $this->laravel->make('funcs');
	}

	/**
	 * Get a seeder instance from the container.
	 */
	protected function getSeeder() {
		$class = $this->input->getArgument('class') ?? $this->input->getOption('class');

		// Lấy namespace root động từ plugin hiện tại
		$rootNamespace = $this->funcs->_getRootNamespace();

		// Nếu chưa có root prefix, tự thêm
		if (strpos($class, $rootNamespace) !== 0) {
			$class = "\\{$rootNamespace}\\{$class}";
		}

		return $this->laravel->make($class)
			->setContainer($this->laravel)
			->setCommand($this);
	}

}
