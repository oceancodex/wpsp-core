<?php

namespace WPSPCORE\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use WPSPCORE\Filesystem\Filesystem;
use WPSPCORE\Funcs;

class Eloquent {

	private ?Capsule  $capsule = null;
	private Funcs     $funcs;
	private string    $mainPath;
	private Migration $migration;
	private string    $rootNamespace;

	/*
	 *
	 */

	public function __construct(Migration $migration, $mainPath, $rootNamespace) {
		$this->funcs         = new Funcs($mainPath);
		$this->mainPath      = $mainPath;
		$this->migration     = $migration;
		$this->rootNamespace = $rootNamespace;
	}


	public function getCapsule(): ?Capsule {
		if (!$this->capsule) {
			$connection    = include($this->funcs->getConfigPath() . '/database.php');
			$this->capsule = new Capsule();
			$this->capsule->addConnection($connection);
			$this->capsule->setAsGlobal();
			$this->capsule->bootEloquent();
		}
		return $this->capsule;
	}

	/*
	 *
	 */

	public function dropDatabaseTable($tableName): string {
		$this->getCapsule()->schema()->withoutForeignKeyConstraints(function () use ($tableName) {
			$this->getCapsule()->schema()->dropIfExists($tableName);
		});
		return $tableName;
	}

	public function createDatabaseTables(): void {
		if (!$this->getCapsule()->schema()->hasTable('abc')) {
			$this->getCapsule()->schema()->create('abc', function (Blueprint $table) {
				$table->increments('id');
			});
		}
	}

	public function dropAllDatabaseTables(): array {
		$definedDatabaseTables = $this->getDefinedDatabaseTables();
		foreach ($definedDatabaseTables as $definedDatabaseTable) {
			$tableDropped = $this->dropDatabaseTable($definedDatabaseTable);
		}
		return ['success' => true, 'data' => $definedDatabaseTables, 'message' => 'Drop all database tables successful!', 'code' => 200];
	}

	public function getDefinedDatabaseTables(): array {
		$databaseTableClasses = $this->funcs->getAllClassesInDir($this->rootNamespace . '\app\Entities', $this->funcs->getAppPath() . '/Entities');
		$databaseTableClasses = array_merge($databaseTableClasses, [$this->funcs->getDBTableName('migration_versions')]);

		$definedDatabaseTables = [];
		foreach ($databaseTableClasses as $databaseTableClass) {
			try {
				$databaseTableName = $this->migration->getEntityManager()->getClassMetadata($databaseTableClass)->getTableName();
			}
			catch (\Exception $e) {
				$databaseTableName = null; // $databaseTableClass;
			}
			if ($databaseTableName) {
				$databaseTableName       = preg_replace('/^' . $this->funcs->getDBTablePrefix() . '/iu', '', $databaseTableName);
				$definedDatabaseTables[] = $databaseTableName;
			}

			try {
				$joinTables = $this->migration->getEntityManager()->getClassMetadata($databaseTableClass)->getAssociationMappings();
				foreach ($joinTables as $joinTable) {
					if (!empty($joinTable?->joinTable?->name)) {
						$joinTableName = $joinTable?->joinTable?->name ?? null;
						if ($joinTableName) {
							$joinTableName           = preg_replace('/^' . $this->funcs->getDBTablePrefix() . '/iu', '', $joinTableName);
							$definedDatabaseTables[] = $joinTableName;
						}
					}
				}
			}
			catch (\Exception $e) {
			}
		}

		$databaseTableMigrations = $this->funcs->getAllFilesInFolder($this->funcs->getMigrationPath());
		foreach ($databaseTableMigrations as $databaseTableMigration) {
			$fileContent    = Filesystem::instance()->get($databaseTableMigration['real_path']);
			$newFileContent = '';
			$tokens         = token_get_all($fileContent);
			foreach ($tokens as $token) {
				if (is_array($token)) {
					if (in_array($token[0], $this->funcs->commentTokens())) {
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
					$createTableName = 'return ' . $createTableName . ';';
					try {
						$createTableName = eval($createTableName);
						if ($createTableName) {
							$createTableName         = str_replace($this->funcs->getDBTablePrefix(), '', $createTableName);
							$definedDatabaseTables[] = $createTableName;
						}
					}
					catch (\Exception $e) {
					}
				}
			}
		}

		return $definedDatabaseTables;
	}

	public function checkDatabaseVersionNewest(): array {
		$databaseVersionIsNewest       = true;
		$lastMigrateVersionInFolder    = $this->migration->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('latest')->__toString();
		$lastMigratedVersionInDatabase = $this->migration->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('current')->__toString();
		if ($lastMigratedVersionInDatabase !== $lastMigrateVersionInFolder) {
			$databaseVersionIsNewest = false;
		}
		return ['result' => $databaseVersionIsNewest, 'type' => 'check_database_version_newest'];
	}

	public function checkAllDatabaseTableExists(): array {
		$definedDatabaseTables   = $this->getDefinedDatabaseTables();
		$allDatabaseTablesExists = true;
		foreach ($definedDatabaseTables as $definedDatabaseTable) {
			$databaseTableExists = $this->getCapsule()->schema()->hasTable($definedDatabaseTable);
			if (!$databaseTableExists) {
				$allDatabaseTablesExists = false;
				break;
			}
		}
		return ['result' => $allDatabaseTablesExists, 'type' => 'check_all_database_table_exists'];
	}

}