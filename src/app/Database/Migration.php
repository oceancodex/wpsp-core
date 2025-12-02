<?php

namespace WPSPCORE\App\Database;

use Illuminate\Contracts\Console\Kernel as ArtisanKernel;
use Illuminate\Filesystem\Filesystem;
use WPSPCORE\BaseInstances;

/**
 * Migration checker tương thích với Application nội bộ (đa plugin).
 * Tương thích PHP 7.4
 */
class Migration extends BaseInstances {

	protected string $migrationTable = 'migrations';
	protected string $migrationPath;

	public function afterConstruct(): void {
		$app                 = $this->funcs->getApplication();
		$this->migrationPath = $app->basePath('database/migrations');
	}

	/**
	 * So sánh migration file và DB
	 */
	public function diff(): array {
		return [
			'missing_tables'   => $this->getMissingTables(),
			'missing_versions' => $this->getMissingMigrationVersions(),
		];
	}

	/**
	 * Tạo bảng migration nếu thiếu
	 */
	public function repair(): array {
		$app    = $this->funcs->getApplication();
		$schema = $app['db']->connection()->getSchemaBuilder();
		$result = [];

		if (!$schema->hasTable($this->migrationTable)) {
			$schema->create($this->migrationTable, function($table) {
				$table->increments('id');
				$table->string('migration')->unique();
				$table->integer('batch')->default(0);
			});
			$result[] = 'Created table: ' . $this->migrationTable;
		}

		return $result;
	}

	/**
	 * Chạy migrate()
	 */
	public function migrate(): array {
		$app     = $this->funcs->getApplication();
		$artisan = $app->make(ArtisanKernel::class);

		$missing = $this->getMissingMigrationVersions();

		if (empty($missing)) {
			return ['success' => true, 'data' => null, 'message' => 'Database is now in latest version.'];
		}

		try {
			$artisan->call('migrate', [
				'--path'  => 'database/migrations',
				'--force' => true,
			]);

			return ['success' => true, 'data' => null, 'message' => 'Migrate database successfully!'];
		}
		catch (\Throwable $e) {
			return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
		}
	}

	/**
	 * Xóa toàn bộ bản ghi migration
	 */
	public function deleteAllMigrations(): array {
		$app    = $this->funcs->getApplication();
		$schema = $app['db']->connection()->getSchemaBuilder();
		$db     = $app['db'];

		if ($schema->hasTable($this->migrationTable)) {
			$db->table($this->migrationTable)->truncate();
			return ['success' => true, 'data' => null, 'message' => 'Deleted all migrations successfully!'];
		}

		return ['success' => false, 'data' => null, 'message' => 'Missing table: ' . $this->migrationTable];
	}

	/**
	 * Lấy danh sách bảng được định nghĩa trong file migration
	 */
	public function getDefinedDatabaseTables(): array {
		$app     = $this->funcs->getApplication();
		$fs      = new Filesystem();
		$defined = [];

		if (!$fs->isDirectory($this->migrationPath)) {
			return $defined;
		}

		$files = $fs->files($this->migrationPath);

		foreach ($files as $file) {
			$content = $fs->get($file->getPathname());
			if (preg_match_all("/Schema::create\(['\"](.*?)['\"]/", $content, $matches)) {
				foreach ($matches[1] as $table) {
					$defined[] = $table;
				}
			}
		}

		return array_unique($defined);
	}

	/**
	 * Kiểm tra toàn bộ DB version
	 */
	public function checkDatabaseVersion(): array {
		$result = $this->checkDatabaseVersionNewest();
		if ($result['result']) {
			$result = $this->checkMigrationFolderNotEmpty();
		}
		if ($result['result']) {
			$result = $this->checkAllDatabaseTableExists();
		}
		return $result;
	}

	/**
	 * Database đã ở version mới nhất chưa?
	 */
	public function checkDatabaseVersionNewest(): array {
		$app    = $this->funcs->getApplication();
		$schema = $app['db']->connection()->getSchemaBuilder();

		if (!$schema->hasTable($this->migrationTable)) {
			return [
				'result' => false,
				'type'   => 'check_database_version_newest',
				'reason' => 'Thiếu bảng ' . $this->migrationTable,
			];
		}

		$missing = $this->getMissingMigrationVersions();

		return [
			'result'           => empty($missing),
			'type'             => 'check_database_version_newest',
			'missing_versions' => $missing,
		];
	}

	/**
	 * Thư mục migrations có file không
	 */
	public function checkMigrationFolderNotEmpty(): array {
		$fs    = new Filesystem();
		$count = 0;

		if ($fs->isDirectory($this->migrationPath)) {
			$count = count($fs->files($this->migrationPath));
		}

		return [
			'result' => $count > 0,
			'type'   => 'check_migration_folder_not_empty',
			'count'  => $count,
		];
	}

	/**
	 * DB có đầy đủ bảng không
	 */
	public function checkAllDatabaseTableExists(): array {
		$app    = $this->funcs->getApplication();
		$schema = $app['db']->connection()->getSchemaBuilder();

		$definedTables = $this->getDefinedDatabaseTables();
		$missing       = [];

		foreach ($definedTables as $table) {
			if (!$schema->hasTable($table)) {
				$missing[] = $table;
			}
		}

		return [
			'result'         => empty($missing),
			'type'           => 'check_all_database_table_exists',
			'missing_tables' => $missing,
		];
	}

	/**
	 * Xóa bảng.
	 */
	public function dropDatabaseTable($tableName) {
		// PHP 8.1+
		$app           = $this->funcs->getApplication('db');
		$schemaBuilder = $app->connection()->getSchemaBuilder();
		$schemaBuilder->withoutForeignKeyConstraints(function() use ($tableName, $schemaBuilder) {
			$schemaBuilder->dropIfExists($tableName);
		});
		return $tableName;
	}

	/**
	 * Xóa toàn bộ bảng.
	 */
	public function dropAllDatabaseTables($output = null): array {
		$tz                    = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('Asia/Ho_Chi_Minh');
		$dt                    = new \DateTime('now', $tz);
		$timestamp             = '[' . $dt->format('Y-m-d H:i:s') . ']';
		$definedDatabaseTables = $this->getDefinedDatabaseTables();
		$definedDatabaseTables = array_merge($definedDatabaseTables, ['migrations']);
		foreach ($definedDatabaseTables as $definedDatabaseTable) {
			$tableDropped = $this->dropDatabaseTable($definedDatabaseTable);
			if ($output) {
				$output->writeln($timestamp . ' <fg=green>[✓] Dropped table: ' . $tableDropped . '</>');
			}
		}
		return [
			'success' => true,
			'data'    => $definedDatabaseTables,
			'message' => 'Drop all database tables successfully!',
		];
	}

	/**
	 * Danh sách bảng chưa tồn tại
	 */
	protected function getMissingTables(): array {
		$app    = $this->funcs->getApplication();
		$schema = $app['db']->connection()->getSchemaBuilder();

		$defined = $this->getDefinedDatabaseTables();
		$missing = [];

		foreach ($defined as $table) {
			if (!$schema->hasTable($table)) {
				$missing[] = $table;
			}
		}

		return $missing;
	}

	/**
	 * Lấy danh sách file migration chưa có trong DB
	 */
	protected function getMissingMigrationVersions(): array {
		$app    = $this->funcs->getApplication();
		$schema = $app['db']->connection()->getSchemaBuilder();
		$db     = $app['db'];
		$fs     = new Filesystem();

		$files = [];
		if ($fs->isDirectory($this->migrationPath)) {
			foreach ($fs->files($this->migrationPath) as $file) {
				$files[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
			}
		}

		if (!$schema->hasTable($this->migrationTable)) {
			$executed = [];
		}
		else {
			$executed = $db->table($this->migrationTable)->pluck('migration')->toArray();
		}

		return array_diff($files, $executed);
	}

}
