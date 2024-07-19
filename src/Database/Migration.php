<?php

namespace WPSPCORE\Database;

use WPSPCORE\Base\BaseInstances;
use WPSPCORE\Database\Extensions\TablePrefix;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use WPSPCORE\Filesystem\Filesystem;
use WPSPCORE\Listeners\MigrationListener;

class Migration extends BaseInstances {

	private ?EntityManager     $entityManager     = null;
	private ?DependencyFactory $dependencyFactory = null;
	private ?Application       $cli               = null;

	/*
	 *
	 */

	public function afterConstruct(): void {
		if (!$this->cli) {
			$this->cli = new Application($this->funcs->_config('app.short_name'));
			$this->cli->setCatchExceptions(true);
			$this->cli->addCommands([
				new Command\DumpSchemaCommand($this->getDependencyFactory()),
				new Command\ExecuteCommand($this->getDependencyFactory()),
				new Command\GenerateCommand($this->getDependencyFactory()),
				new Command\LatestCommand($this->getDependencyFactory()),
				new Command\ListCommand($this->getDependencyFactory()),
				new Command\MigrateCommand($this->getDependencyFactory()),
				new Command\RollupCommand($this->getDependencyFactory()),
				new Command\StatusCommand($this->getDependencyFactory()),
				new Command\SyncMetadataCommand($this->getDependencyFactory()),
				new Command\VersionCommand($this->getDependencyFactory()),
				new Command\DiffCommand($this->getDependencyFactory()),
			]);
		}
	}

	/*
	 *
	 */

	public function global(): void {
		$globalMigration = $this->funcs->_getAppShortName();
		$globalMigration = $globalMigration . '_migration';
		global ${$globalMigration};
		${$globalMigration} = $this;
	}

	/*
	 *
	 */

	public function cli(): ?Application {
		return $this->cli;
	}

	/*
	 *
	 */

	public function diff(): array {
		try {
			$input  = new ArrayInput([
				'command'             => 'diff',
				'--no-interaction'    => true,
				'--filter-expression' => '/^' . $this->funcs->_getDBTablePrefix() . '((?!cm))/iu',
			]);
			$output = new BufferedOutput();
			$this->cli()->doRun($input, $output);
			return ['success' => true, 'message' => 'Generate new database migration successfully!', 'data' => ['output' => $output->fetch()]];
		}
		catch (\Exception|\Throwable $e) {
			return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
		}
	}

	public function repair(): array {
		$lastMigratedVersion            = $this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('current')->__toString();
		$lastMigrateVersionInFolder     = $this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('latest')->__toString();
		$lastMigrateVersionNameInFolder = preg_replace('/^(.*?)migrations\\\(.*?)$/iu', '$2', $lastMigrateVersionInFolder);
		$lastMigrateVersionPathInFolder = $this->funcs->_getMigrationPath() . '/' . $lastMigrateVersionNameInFolder . '.php';
		$result                         = [];
		if ($lastMigratedVersion !== $lastMigrateVersionInFolder) {
			try {
//				$input = new ArrayInput([
//					'command' => 'version',
//					'version' => $lastMigrateVersionInFolder,
//					'--delete' => true
//				]);
//				$output = new BufferedOutput();
//				$this->cli()->doRun($input, $output);
				$exists = Filesystem::exists($lastMigrateVersionPathInFolder);
				if ($exists) {
					try {
						Filesystem::delete($lastMigrateVersionPathInFolder);
						$result = ['success' => true, 'message' => 'Repaired database successfully! [Deleted: ' . $lastMigrateVersionNameInFolder . ']', 'data' => null];
					}
					catch (\Exception $exception) {
						$result = ['success' => false, 'message' => $exception->getMessage(), 'data' => null];
					}
				}
				else {
					$result = ['success' => false, 'message' => 'Last migrate version in folder not exists!', 'data' => null];
				}
			}
			catch (\Exception $e) {
				$result = ['success' => false, 'message' => $e->getMessage(), 'data' => null];
			}
		}
		else {
			$result = ['success' => false, 'message' => 'Your database does not need to be repaired!', 'data' => null];
		}
		return $result;
	}

	public function migrate(): array {
		try {
			$input  = new ArrayInput([
				'command'          => 'migrate',
				'--no-interaction' => true,
			]);
			$output = new BufferedOutput();
			$this->cli()->doRun($input, $output);
			$outputMessage = $output->fetch();
			$outputMessage = preg_replace('/\n*|\r\n*/iu', '', $outputMessage);
			$outputMessage = preg_replace('/^(.+?)yes](.+?)\[/iu', '[', $outputMessage);
			$outputMessage = preg_replace('/^\[(.+?)]\s/iu', '', $outputMessage);
			$outputMessage = preg_replace('/>>/', '<br/>>>', $outputMessage);
			$outputMessage = preg_replace('/\[OK]/iu', '<br/>[OK]', $outputMessage);
			$outputMessage = preg_replace('/\[notice]/iu', '<br/>[Notice]', $outputMessage);
			if (preg_match('/Successfully|Already|Migrating/iu', $outputMessage)) {
				return ['success' => true, 'data' => ['output' => $outputMessage], 'message' => 'Migrate database successfully!'];
			}
			else {
				return ['success' => false, 'data' => ['output' => $outputMessage], 'message' => $outputMessage];
			}
		}
		catch (\Exception|\Throwable $e) {
			return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
		}
	}

	/*
	 *
	 */

	public function getEntityManager(): EntityManager {
		if (!$this->entityManager) {
			$paths            = [$this->funcs->_getAppPath() . '/Entities'];
			$isDevMode        = $this->funcs->_config('app.env') == 'dev' || $this->funcs->_config('app.env') == 'local';
			$tablePrefix      = new TablePrefix($this->funcs->_getDBTablePrefix());
			$connectionParams = include($this->funcs->_getConfigPath() . '/migrations-db.php');

			$eventManager = new EventManager();
			$eventManager->addEventListener(Events::loadClassMetadata, $tablePrefix);
			$eventManager->addEventSubscriber(new MigrationListener());

			$ormConfig  = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode);
			$connection = DriverManager::getConnection($connectionParams);

			$this->entityManager = new EntityManager($connection, $ormConfig, $eventManager);
		}
		return $this->entityManager;
	}

	public function getDependencyFactory(): DependencyFactory {
		if (!$this->dependencyFactory) {
			$config                  = new PhpFile($this->funcs->_getConfigPath() . '/migrations.php');
			$existingEntityManager   = new ExistingEntityManager($this->getEntityManager());
			$this->dependencyFactory = DependencyFactory::fromEntityManager($config, $existingEntityManager);
		}
		return $this->dependencyFactory;
	}

	/*
	 *
	 */

	public function syncMetadata(): array {
		try {
			$input  = new ArrayInput([
				'command'          => 'sync-metadata-storage',
				'--no-interaction' => true,
			]);
			$output = new BufferedOutput();
			$this->cli()->doRun($input, $output);
			return ['success' => true, 'message' => 'Sync metadata successfully!', 'data' => $output->fetch()];
		}
		catch (\Exception|\Throwable $e) {
			return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
		}
	}

	public function deleteAllMigrations(): array {
		$allMigrations     = $this->getDependencyFactory()->getMigrationsFinder()->findMigrations($this->funcs->_trailingslashit($this->funcs->_getMigrationPath()));
		$deletedMigrations = [];
		foreach ($allMigrations as $migrationVersion) {
			if (!preg_match('/_/iu', $migrationVersion)) {
				$migrationVersion     = preg_replace('/^(.*?)migrations\/(.*?)/iu', '$2', $this->funcs->_trailingslash($migrationVersion));
				$migrationVersionPath = $this->funcs->_trailingslash($this->funcs->_getMigrationPath() . '/' . $migrationVersion . '.php');
//			    $migrationVersionPathFromPluginDir = _getPathFromDir('plugins', $migrationVersionPath) . '.php';
				$deletedMigrations[] = Filesystem::delete($migrationVersionPath);
			}
		}
		return $this->funcs->_response(true, $deletedMigrations, 'Deleted all migrations successfully!', 200);
	}

	public function getDefinedDatabaseTables(): array {
		$databaseTableClasses = $this->funcs->_getAllClassesInDir($this->rootNamespace . '\app\Entities', $this->funcs->_getAppPath() . '/Entities');
		$databaseTableClasses = array_merge($databaseTableClasses, [$this->funcs->_getDBTableName('migration_versions')]);

		$definedDatabaseTables = [];
		foreach ($databaseTableClasses as $databaseTableClass) {
			try {
				$databaseTableName = $this->getEntityManager()->getClassMetadata($databaseTableClass)->getTableName();
			}
			catch (\Exception $e) {
				$databaseTableName = null; // $databaseTableClass;
			}
			if ($databaseTableName) {
				$databaseTableName       = preg_replace('/^' . $this->funcs->_getDBTablePrefix() . '/iu', '', $databaseTableName);
				$definedDatabaseTables[] = $databaseTableName;
			}

			try {
				$joinTables = $this->getEntityManager()->getClassMetadata($databaseTableClass)->getAssociationMappings();
				foreach ($joinTables as $joinTable) {
					if (!empty($joinTable?->joinTable?->name)) {
						$joinTableName = $joinTable?->joinTable?->name ?? null;
						if ($joinTableName) {
							$joinTableName           = preg_replace('/^' . $this->funcs->_getDBTablePrefix() . '/iu', '', $joinTableName);
							$definedDatabaseTables[] = $joinTableName;
						}
					}
				}
			}
			catch (\Exception $e) {
			}
		}

		$databaseTableMigrations = $this->funcs->_getAllFilesInFolder($this->funcs->_getMigrationPath());
		foreach ($databaseTableMigrations as $databaseTableMigration) {
			$fileContent    = Filesystem::instance()->get($databaseTableMigration['real_path']);
			$newFileContent = '';
			$tokens         = token_get_all($fileContent);
			foreach ($tokens as $token) {
				if (is_array($token)) {
					if (in_array($token[0], $this->funcs->_commentTokens())) {
						continue;
					}
					$token = $token[1];
				}
				$newFileContent .= $token;
			}
			preg_match_all('/createTable\(([\S\s]*?);/iu', $newFileContent, $createTables);
			$createTableNames = $createTables[1] ?? $createTables[0] ?? null;
			if ($createTableNames) {
				foreach ($createTableNames as $createTableName) {
					$createTableName = preg_replace('/\)$/', '', $createTableName);
					$createTableName = preg_replace('/Funcs::instance\(\)->|Funcs::/', '$this->funcs->', $createTableName);
					$createTableName = preg_replace('/getDB/', '_getDB', $createTableName);
					$createTableName = 'return ' . $createTableName . ';';
					try {
						$createTableName = eval($createTableName);
						if ($createTableName) {
							$createTableName         = str_replace($this->funcs->_getDBTablePrefix(), '', $createTableName);
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

	/*
	 *
	 */

	public function checkDatabaseVersion(): ?array {
		$databaseIsValid = $this->checkDatabaseVersionNewest();
		if ($databaseIsValid['result']) {
			$databaseIsValid = $this->checkMigrationFolderNotEmpty();
		}
		if ($databaseIsValid['result']) {
			$databaseIsValid = $this->checkAllDatabaseTableExists();
		}
		return $databaseIsValid;
	}

	public function checkDatabaseVersionNewest(): array {
		$databaseVersionIsNewest       = true;
		$lastMigrateVersionInFolder    = $this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('latest')->__toString();
		$lastMigratedVersionInDatabase = $this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('current')->__toString();
		if ($lastMigratedVersionInDatabase !== $lastMigrateVersionInFolder) {
			$databaseVersionIsNewest = false;
		}
		return ['result' => $databaseVersionIsNewest, 'type' => 'check_database_version_newest'];
	}

	public function checkMigrationFolderNotEmpty(): array {
		$migrationCounts = $this->getDependencyFactory()->getMigrationRepository()->getMigrations()->count();
		return ['result' => $migrationCounts, 'type' => 'check_migration_folder_not_empty'];
	}

	public function checkAllDatabaseTableExists(): array {
		$definedDatabaseTables   = $this->getDefinedDatabaseTables();
		$allDatabaseTablesExists = true;
		foreach ($definedDatabaseTables as $definedDatabaseTable) {
			$databaseTableExists = $this->funcs->_getAppEloquent()->getCapsule()->getDatabaseManager()->getSchemaBuilder()->hasTable($definedDatabaseTable);
			if (!$databaseTableExists) {
				$allDatabaseTablesExists = false;
				break;
			}
		}
		return ['result' => $allDatabaseTablesExists, 'type' => 'check_all_database_table_exists'];
	}

}