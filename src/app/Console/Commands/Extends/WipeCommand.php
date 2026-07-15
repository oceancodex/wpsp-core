<?php

namespace WPSPCORE\App\Console\Commands\Extends;

use Illuminate\Database\Console\WipeCommand as Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class WipeCommand extends Command {

	use CommandsTrait;

//	protected $name        = 'db:wipe';
	protected $description = 'Drop all tables with a specific prefix from the database. [WPSP]';

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
	 * Execute the console command.
	 */
	protected function dropAllTables($database) {
		/** @var \Illuminate\Database\Connection $connection */
		$connection = $this->funcs->_getApplication('db')->connection($database);
		$schema     = $connection->getSchemaBuilder();
		$prefix     = $connection->getTablePrefix();

		// 1. Lấy chính xác tên database hiện tại từ config connection
		$currentDatabaseName = $connection->getDatabaseName();

		// 2. Lấy danh sách bảng và lọc nghiêm ngặt theo database hiện tại + prefix
		$tables = collect($schema->getTables())
			->filter(function($table) use ($currentDatabaseName, $prefix) {
				// Một số driver trả về object chứa thông tin 'schema' hoặc 'database'
				// Ta kiểm tra nếu có trường 'schema'/'database' thì phải khớp với database hiện tại
				$belongsToCurrentDb = true;

				if (isset($table['schema']) && $table['schema'] !== $currentDatabaseName) {
					$belongsToCurrentDb = false;
				}
				elseif (isset($table['database']) && $table['database'] !== $currentDatabaseName) {
					$belongsToCurrentDb = false;
				}

				return $belongsToCurrentDb && str_starts_with($table['name'], $prefix);
			})
			->pluck('name')
			->all();

		$schema->disableForeignKeyConstraints();

		foreach ($tables as $table) {
			// Khi drop, ta cần xóa tiền tố (prefix) ra vì Laravel's Schema Builder 
			// sẽ tự động tự thêm prefix vào khi thực thi câu lệnh drop().
			$tableNameWithoutPrefix = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $table);

			$schema->drop($tableNameWithoutPrefix);
		}

		$schema->enableForeignKeyConstraints();
	}

}
