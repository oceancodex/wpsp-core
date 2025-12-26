<?php

namespace WPSPCORE\App\Console\Commands\Extends;

use Illuminate\Database\Console\Seeds\SeederMakeCommand as Command;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class SeederMakeCommand extends Command {

	use CommandsTrait;

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new seeder class. [WPSP]';

	/**
	 * Initializes the command after the input has been bound and before the input
	 * is validated.
	 *
	 * This is mainly useful when a lot of commands extends one main command
	 * where some things need to be initialized based on the input arguments and options.
	 */
	protected function initialize($input, $output) {
		parent::initialize($input, $output);
		$this->funcs = $this->laravel->make('funcs');
	}

	/**
	 * Get the root namespace for the class.
	 *
	 * @return string
	 */
	protected function rootNamespace() {
		return $this->funcs->_getRootNamespace() . '\\Database\Seeders\\';
	}

}
