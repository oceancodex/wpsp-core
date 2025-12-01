<?php

namespace WPSPCORE\Console\Commands\Extends;

use Illuminate\Foundation\Console\KeyGenerateCommand as Command;
use WPSPCORE\Console\Traits\CommandsTrait;

class KeyGenerateCommand extends Command {

	use CommandsTrait;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'key:generate
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Set the application key';

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
	 * Write a new environment file with the given key.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	protected function writeNewEnvironmentFileWith($key): bool {
		$replaced = preg_replace(
			$this->keyReplacementPattern(),
			$this->funcs->_getRootNamespace() . '_APP_KEY=' . $key,
			$input = file_get_contents($this->laravel->environmentFilePath())
		);

		if ($replaced === $input || $replaced === null) {
			$this->error('Unable to set application key. No APP_KEY variable was found in the .env file.');

			return false;
		}

		file_put_contents($this->laravel->environmentFilePath(), $replaced);

		return true;
	}

	/**
	 * Get a regex pattern that will match env APP_KEY with any random key.
	 *
	 * @return string
	 */
	protected function keyReplacementPattern(): string {
		$escaped = preg_quote('=' . $this->laravel['config']['app.key'], '/');

		return '/^'.$this->funcs->_getRootNamespace().'_APP_KEY'.$escaped.'/m';
	}

}
