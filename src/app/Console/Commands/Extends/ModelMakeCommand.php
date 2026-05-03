<?php

namespace WPSPCORE\App\Console\Commands\Extends;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class ModelMakeCommand extends \Illuminate\Foundation\Console\ModelMakeCommand {

	use CommandsTrait;

//	protected $name        = 'db:wipe';
	protected $description = 'Create a new Eloquent model class. [WPSP]';

	/**
	 * Initializes the command after the input has been bound and before the input
	 * is validated.
	 *
	 * This is mainly useful when a lot of commands extends one main command
	 * where some things need to be initialized based on the input arguments and options.
	 */
	protected function initialize(InputInterface $input, OutputInterface $output): void {
		parent::initialize($input, $output);
		$this->funcs = $this->laravel->make('funcs');
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub() {
		if ($this->option('pivot')) {
			return __DIR__ . '/../../Stubs/Extends/MakeModelCommand/model.pivot.stub';
		}

		if ($this->option('morph-pivot')) {
			return __DIR__ . '/../../Stubs/Extends/MakeModelCommand/model.morph-pivot.stub';
		}

		return __DIR__ . '/../../Stubs/Extends/MakeModelCommand/model.stub';
	}

}
