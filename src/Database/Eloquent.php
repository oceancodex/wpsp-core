<?php

namespace WPSPCORE\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use MongoDB\Laravel\Eloquent\Model;
use WPSPCORE\Base\BaseInstances;
use WPSPCORE\Filesystem\Filesystem;

class Eloquent extends BaseInstances {

	public ?Capsule $capsule    = null;
	public string   $connection = 'mysql';

	/*
	 *
	 */

	public function afterConstruct(): void {
//		if (!$this->capsule) {
			$this->capsule  = new Capsule(new Container());

			$this->capsule->getDatabaseManager()->extend('mongodb', function($config, $name) {
				$config['name'] = $name;
				return new \MongoDB\Laravel\Connection($config);
			});

			$wrapDbConfigsKey = $this->funcs->_getAppShortName() . '_database_configs';

//			global $wpspDatabaseConnections;
			$wpspDatabaseConnections = array_merge(
				$wpspDatabaseConnections ?? [],
				[$wrapDbConfigsKey => $this->funcs->_config('database')]
			);

			$defaultConnectionName = $this->funcs->_getAppShortName() . '_' . $this->funcs->_config('database.default');
			$defaultConnectionConfig = $wpspDatabaseConnections[$wrapDbConfigsKey]['connections'][$defaultConnectionName];
			$this->capsule->addConnection($defaultConnectionConfig);

			foreach ($wpspDatabaseConnections[$wrapDbConfigsKey]['connections'] as $connectionName => $connectionConfig) {
				$this->capsule->addConnection($connectionConfig, $connectionName);
			}

//			$this->capsule->setAsGlobal();
			$this->capsule->bootEloquent();
//		}
	}

	/*
	 *
	 */

	public function global(): void {
		$globalEloquent = $this->funcs->_getAppShortName();
		$globalEloquent = $globalEloquent . '_eloquent';
		global ${$globalEloquent};
		${$globalEloquent} = $this;
	}

	/*
	 *
	 */

	public function getCapsule(): ?Capsule {
		return $this->capsule;
	}

	/*
	 *
	 */

	public function dropDatabaseTable($tableName): string {
		$this->funcs->_getAppEloquent()->getCapsule()->getDatabaseManager()->getSchemaBuilder()->withoutForeignKeyConstraints(function() use ($tableName) {
			$this->getCapsule()->getDatabaseManager()->getSchemaBuilder()->dropIfExists($tableName);
		});
		return $tableName;
	}

	public function dropAllDatabaseTables(): array {
		$definedDatabaseTables = $this->funcs->_getAppMigration()->getDefinedDatabaseTables();
		$definedDatabaseTables = array_merge($definedDatabaseTables, ['migration_versions']);
		foreach ($definedDatabaseTables as $definedDatabaseTable) {
			$tableDropped = $this->dropDatabaseTable($definedDatabaseTable);
		}
		return ['success' => true, 'data' => $definedDatabaseTables, 'message' => 'Drop all database tables successfully!', 'code' => 200];
	}

}