<?php

namespace WPSPCORE\Database;

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
use WPSP\Funcs;

class Migration {
	private static ?EntityManager     $entityManager     = null;
	private static ?DependencyFactory $dependencyFactory = null;
	private static ?Application       $cli               = null;

	public static function getCLI(): Application {
		if (!self::$cli) {
			self::$cli = new Application(config('app.short_name'));
			self::$cli->setCatchExceptions(true);
			self::$cli->addCommands([
				new Command\DumpSchemaCommand(self::getDependencyFactory()),
				new Command\ExecuteCommand(self::getDependencyFactory()),
				new Command\GenerateCommand(self::getDependencyFactory()),
				new Command\LatestCommand(self::getDependencyFactory()),
				new Command\ListCommand(self::getDependencyFactory()),
				new Command\MigrateCommand(self::getDependencyFactory()),
				new Command\RollupCommand(self::getDependencyFactory()),
				new Command\StatusCommand(self::getDependencyFactory()),
				new Command\SyncMetadataCommand(self::getDependencyFactory()),
				new Command\VersionCommand(self::getDependencyFactory()),
				new Command\DiffCommand(self::getDependencyFactory()),
			]);
		}
		return self::$cli;
	}

	/*
	 *
	 */

	public static function diff(): array {
		try {
			$input  = new ArrayInput([
				'command'             => 'diff',
				'--no-interaction'    => true,
				'--filter-expression' => '/^' . _dbTablePrefix() . '((?!cm))/iu',
			]);
			$output = new BufferedOutput();
			self::getCLI()->doRun($input, $output);
//			echo '<pre>'; print_r($migration->getDependencyFactory()->getMigrationsFinder()->findMigrations(WPSP_MIGRATION_PATH)); echo '</pre>';
//			echo '<pre>'; print_r($migration->getDependencyFactory()->getMigrationStatusCalculator()->getNewMigrations()->getLast()->getVersion()->__toString()); echo '</pre>';
			return ['success' => true, 'message' => 'Generate new database migration successfully!', 'data' => ['output' => $output->fetch()]];
		}
		catch (\Exception|\Throwable $e) {
			return ['success' => false, 'data' => null, 'message' => $e->getMessage()];
		}
	}

	public static function repair(): array {
		$lastMigratedVersion            = self::getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('current')->__toString();
		$lastMigrateVersionInFolder     = self::getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('latest')->__toString();
		$lastMigrateVersionNameInFolder = preg_replace('/^(.*?)migrations\\\(.*?)$/iu', '$2', $lastMigrateVersionInFolder);
		$lastMigrateVersionPathInFolder = Funcs::instance()->getMigrationPath() . '/' . $lastMigrateVersionNameInFolder . '.php';
		$result                         = [];
		if ($lastMigratedVersion !== $lastMigrateVersionInFolder) {
			try {
//				$input = new ArrayInput([
//					'command' => 'version',
//					'version' => $lastMigrateVersionInFolder,
//					'--delete' => true
//				]);
//				$output = new BufferedOutput();
//				$this->getCLI()->doRun($input, $output);
				$exists = FileHandler::getFileSystem()->exists($lastMigrateVersionPathInFolder);
				if ($exists) {
					try {
						FileHandler::getFileSystem()->delete($lastMigrateVersionPathInFolder);
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

	public static function migrate(): array {
		try {
			$input  = new ArrayInput([
				'command'          => 'migrate',
				'--no-interaction' => true,
			]);
			$output = new BufferedOutput();
			self::getCLI()->doRun($input, $output);
			return ['success' => true, 'message' => 'Migrate database successful!', 'data' => $output->fetch()];
		}
		catch (\Exception|\Throwable $e) {
			return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
		}
	}

	/*
	 *
	 */

	public static function getEntityManager(): EntityManager {
		if (!self::$entityManager) {
			$paths            = [Funcs::instance()->getAppPath() . '/Entities'];
			$isDevMode        = config('app.env') == 'dev' || config('app.env') == 'local';
			$tablePrefix      = new TablePrefix(_dbTablePrefix());
			$connectionParams = include(Funcs::instance()->getConfigPath() . '/migrations-db.php');

			$eventManager = new EventManager();
			$eventManager->addEventListener(Events::loadClassMetadata, $tablePrefix);

			$ormConfig  = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode);
			$connection = DriverManager::getConnection($connectionParams);

			self::$entityManager = new EntityManager($connection, $ormConfig, $eventManager);
		}
		return self::$entityManager;
	}

	public static function getDependencyFactory(): DependencyFactory {
		if (!self::$dependencyFactory) {
			$config                  = new PhpFile(Funcs::instance()->getConfigPath() . '/migrations.php');
			$existingEntityManager   = new ExistingEntityManager(self::getEntityManager());
			self::$dependencyFactory = DependencyFactory::fromEntityManager($config, $existingEntityManager);
		}
		return self::$dependencyFactory;
	}

	/*
	 *
	 */

	public static function syncMetadata(): array {
		try {
			$input  = new ArrayInput([
				'command'          => 'sync-metadata-storage',
				'--no-interaction' => true,
			]);
			$output = new BufferedOutput();
			self::getCLI()->doRun($input, $output);
			return ['success' => true, 'message' => 'Sync metadata successful!', 'data' => $output->fetch()];
		}
		catch (\Exception|\Throwable $e) {
			return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
		}
	}

	public static function deleteAllMigrations(): array {
		$allMigrations     = self::getDependencyFactory()->getMigrationsFinder()->findMigrations(_trailingslashit(Funcs::instance()->getMigrationPath()));
		$deletedMigrations = [];
		foreach ($allMigrations as $migrationVersion) {
			if (!preg_match('/_/iu', $migrationVersion)) {
				$migrationVersion     = preg_replace('/^(.*?)migrations\/(.*?)/iu', '$2', _trailingslash($migrationVersion));
				$migrationVersionPath = _trailingslash(Funcs::instance()->getMigrationPath() . '/' . $migrationVersion . '.php');
//			    $migrationVersionPathFromPluginDir = _getPathFromDir('plugins', $migrationVersionPath) . '.php';
				$deletedMigrations[] = FileHandler::deleteFile($migrationVersionPath);
			}
		}
		return _response(true, $deletedMigrations, 'Deleted all migrations successful!', 200);
	}

	public static function checkDatabaseVersion(): ?array {
		$databaseIsValid = Eloquent::checkDatabaseVersionNewest();
		if ($databaseIsValid['result']) {
			$databaseIsValid = Migration::checkMigrationFolderNotEmpty();
		}
		if ($databaseIsValid['result']) {
			$databaseIsValid = Eloquent::checkAllDatabaseTableExists();
		}
		return $databaseIsValid;
	}

	public static function checkMigrationFolderNotEmpty(): array {
		$migrationCounts = self::getDependencyFactory()->getMigrationRepository()->getMigrations()->count();
		return ['result' => $migrationCounts, 'type' => 'check_migration_folder_not_empty'];
	}
}