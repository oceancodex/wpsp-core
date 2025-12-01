<?php

namespace WPSPCORE\Console\Commands\Extends;

use WPSPCORE\Console\Traits\CommandsTrait;

class WipeCommand extends \Illuminate\Database\Console\WipeCommand {

	use CommandsTrait;

	protected $name        = 'db:wipe';
	protected $description = 'Drop all tables with a specific prefix from the database.';

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
	 * Execute the console command.
	 */
	public function handle(): void {
		/** @var \Illuminate\Database\DatabaseManager $db */
		$db = $this->laravel->make('db');

		$database   = $this->option('database') ?: config('database.default');
		$connection = $db->connection($database);
		$prefix     = $connection->getTablePrefix();

		if (empty($prefix)) {
			$this->components->error("No prefix configured for connection [{$database}]. Aborting to protect WordPress tables.");
			return;
		}

		$dbName = $connection->getDatabaseName();

		// lấy danh sách bảng
		$tables = $connection->select("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = ?
          AND table_name LIKE ?
    ", [$dbName, $prefix.'%']);

		$tableNames = array_map(fn($t) => $t->table_name, $tables);

		if (empty($tableNames)) {
			$this->components->info("No tables found with prefix [{$prefix}] in [{$dbName}].");
			return;
		}

		$this->components->warn("Dropping tables with prefix [{$prefix}] on connection [{$database}]...");

		$schema = $connection->getSchemaBuilder();
		$schema->disableForeignKeyConstraints();

		foreach ($tableNames as $table) {
			$connection->statement("DROP TABLE IF EXISTS `$table`");
		}

		$schema->enableForeignKeyConstraints();

		$this->components->info("Dropped " . count($tableNames) . " tables with prefix [{$prefix}].");
	}

}
