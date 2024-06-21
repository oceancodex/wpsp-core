<?php

namespace OCBPCORE\Objects\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use OCBPCORE\Objects\File\FileHandler;

class Eloquent {

	public static ?Capsule $capsule = null;

	/*
	 *
	 */

	public static function init(): void {
		self::getCapsule();
	}

	public static function getCapsule(): ?Capsule {
		if (!self::$capsule) {
			$connection = self::getConnection();
			self::$capsule = new Capsule();
			self::$capsule->addConnection($connection);
			self::$capsule->setAsGlobal();
			self::$capsule->bootEloquent();
		}
		return self::$capsule;
	}

	/*
	 *
	 */

	public static function getConnection($connection = 'mysql') {
		$database = include(OCBP_CONFIG_PATH . '/database.php');
		return $database['connections'][$connection];
	}

	/*
	 *
	 */

	public static function dropDatabaseTable($tableName): string {
		self::getCapsule()->schema()->withoutForeignKeyConstraints(function() use ($tableName) {
			self::getCapsule()->schema()->dropIfExists($tableName);
		});
		return $tableName;
	}

	public static function createDatabaseTables(): void {
		if (!self::getCapsule()->schema()->hasTable('abc')) {
			self::getCapsule()->schema()->create('abc', function (Blueprint $table) {
				$table->increments('id');
			});
		}
	}

	public static function dropAllDatabaseTables(): array {
		$definedDatabaseTables = self::getDefinedDatabaseTables();
		foreach ($definedDatabaseTables as $definedDatabaseTable) {
			$tableDropped = self::dropDatabaseTable($definedDatabaseTable);
		}
		return ['success' => true, 'data' => $definedDatabaseTables, 'message' => 'Drop all database tables successful!', 'code' => 200];
	}

	public static function getDefinedDatabaseTables(): array {
		$databaseTableClasses  = _getAllClassesInDir('OCBP\app\Entities', OCBP_APP_PATH . '/Entities');
		$databaseTableClasses  = array_merge($databaseTableClasses, [_dbTableName('migration_versions')]);

		$definedDatabaseTables = [];
		foreach ($databaseTableClasses as $databaseTableClass) {
			try {
				$databaseTableName = Migration::getEntityManager()->getClassMetadata($databaseTableClass)->getTableName();
			}
			catch (\Exception $e) {
				$databaseTableName = null; // $databaseTableClass;
			}
			if ($databaseTableName) {
				$databaseTableName = preg_replace('/^' . _dbTablePrefix() . '/iu', '', $databaseTableName);
				$definedDatabaseTables[] = $databaseTableName;
			}

			try {
				$joinTables = Migration::getEntityManager()->getClassMetadata($databaseTableClass)->getAssociationMappings();
				foreach ($joinTables as $joinTable) {
					if (!empty($joinTable?->joinTable?->name)) {
						$joinTableName = $joinTable?->joinTable?->name ?? null;
						if ($joinTableName) {
							$joinTableName = preg_replace('/^' . _dbTablePrefix() . '/iu', '', $joinTableName);
							$definedDatabaseTables[] = $joinTableName;
						}
					}
				}
			}
			catch (\Exception $e) {}
		}

		$databaseTableMigrations = _getAllFilesInFolder(OCBP_MIGRATION_PATH);
		foreach ($databaseTableMigrations as $databaseTableMigration) {
			$fileContent = FileHandler::getFileSystem()->get($databaseTableMigration['real_path']);
			$newFileContent = '';
			$tokens = token_get_all($fileContent);
			foreach ($tokens as $token) {
				if (is_array($token)) {
					if (in_array($token[0], _commentTokens())) {
						continue;
					}
					$token = $token[1];
				}
				$newFileContent .= $token;
			}
			preg_match_all('/createTable\(([\S\s]*?)\)/iu', $newFileContent, $createTables);
			$createTableNames = $createTables[1] ?? $createTables[0] ?? null;
			if ($createTableNames) {
				foreach ($createTableNames as $createTableName) {
					if (preg_match('/\(/iu', $createTableName)) {
						$createTableName .= ')';
					}
					$createTableName = 'return '. $createTableName. ';';
					try {
						$createTableName = eval($createTableName);
						if ($createTableName) {
							$createTableName = str_replace(_dbTablePrefix(), '', $createTableName);
							$definedDatabaseTables[] = $createTableName;
						}
					}
					catch (\Exception $e) {}
				}
			}
		}

		return $definedDatabaseTables;
	}

	public static function checkDatabaseVersionNewest(): array {
		$databaseVersionIsNewest       = true;
		$lastMigrateVersionInFolder    = Migration::getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('latest')->__toString();
		$lastMigratedVersionInDatabase = Migration::getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('current')->__toString();
		if ($lastMigratedVersionInDatabase !== $lastMigrateVersionInFolder) {
			$databaseVersionIsNewest = false;
		}
		return ['result' => $databaseVersionIsNewest, 'type' => 'check_database_version_newest'];
	}

	public static function checkAllDatabaseTableExists(): array {
		$definedDatabaseTables   = self::getDefinedDatabaseTables();
		$allDatabaseTablesExists = true;
		foreach ($definedDatabaseTables as $definedDatabaseTable) {
			$databaseTableExists = self::getCapsule()->schema()->hasTable($definedDatabaseTable);
			if (!$databaseTableExists) {
				$allDatabaseTablesExists = false;
				break;
			}
		}
		return ['result' => $allDatabaseTablesExists, 'type' => 'check_all_database_table_exists'];
	}

}