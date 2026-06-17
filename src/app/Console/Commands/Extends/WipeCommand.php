<?php

namespace WPSPCORE\App\Console\Commands\Extends;

use WPSPCORE\App\Console\Traits\CommandsTrait;

class WipeCommand extends \Illuminate\Database\Console\WipeCommand {

	use CommandsTrait;

//	protected $name        = 'db:wipe';
	protected $description = 'Drop all tables with a specific prefix from the database. [WPSP]';

	/**
	 * Execute the console command.
	 */
	protected function dropAllTables($database) {
		$connection = $this->laravel['db']->connection($database);
		$schema     = $connection->getSchemaBuilder();
		$prefix     = $connection->getTablePrefix();
		$tables     = collect($connection->getSchemaBuilder()->getTables())->pluck('name')->filter(fn($table) => str_starts_with($table, $prefix))->all();

		$schema->disableForeignKeyConstraints();

		foreach ($tables as $table) {
			$schema->drop(
				str_replace($prefix, '', $table)
			);
		}

		$schema->enableForeignKeyConstraints();
	}

}
