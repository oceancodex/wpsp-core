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
	 * Get the root namespace for the class.
	 *
	 * @return string
	 */
	protected function rootNamespace() {
		return 'WPSP\\Database\Seeders\\';
	}

}
