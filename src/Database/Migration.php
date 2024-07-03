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
	private ?Eloquent          $eloquent          = null;

	/*
	 *
	 */

	public function afterConstruct(): void {
		$this->eloquent = Eloquent::instance(
			$this->mainPath,
			$this->rootNamespace,
			$this->prefixEnv
		);
		$this->eloquent->setMigration($this);
	}

	/*
	 *
	 */

	public function getCli(): Application {
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
		return $this->cli;
	}

	public function getEloquent(): ?Eloquent {
		return $this->eloquent;
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
			$this->getCli()->doRun($input, $output);
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
//				$this->getCLI()->doRun($input, $output);
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
			$this->getCLI()->doRun($input, $output);
			$outputMessage = $output->fetch();
			$outputMessage = preg_replace('/\n*|\r\n*/', '', $outputMessage);
			$outputMessage = preg_replace('/^(.+?)yes](.+?)\[/', '[', $outputMessage);
			$outputMessage = preg_replace('/^\[(.+?)]\s/', '', $outputMessage);
			if (preg_match('/successfully/iu', $outputMessage)) {
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
			$this->getCLI()->doRun($input, $output);
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

	public function checkDatabaseVersion(): ?array {
		$databaseIsValid = $this->eloquent->checkDatabaseVersionNewest();
		if ($databaseIsValid['result']) {
			$databaseIsValid = $this->checkMigrationFolderNotEmpty();
		}
		if ($databaseIsValid['result']) {
			$databaseIsValid = $this->eloquent->checkAllDatabaseTableExists();
		}
		return $databaseIsValid;
	}

	public function checkMigrationFolderNotEmpty(): array {
		$migrationCounts = $this->getDependencyFactory()->getMigrationRepository()->getMigrations()->count();
		return ['result' => $migrationCounts, 'type' => 'check_migration_folder_not_empty'];
	}

}