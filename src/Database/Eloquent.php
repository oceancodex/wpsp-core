<?php

namespace WPSPCORE\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use WPSPCORE\Base\BaseInstances;
use WPSPCORE\Filesystem\Filesystem;

class Eloquent extends BaseInstances {

	public ?Capsule $capsule    = null;
	public string   $connection = 'mysql';

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

	public function afterConstruct(): void {
		if (!$this->capsule) {
			$this->capsule  = new Capsule();
			global $wpspDatabaseConnections;
			$wpspDatabaseConnections = array_merge(is_array($wpspDatabaseConnections) ? $wpspDatabaseConnections : [], $this->funcs->_config('database.connections'));

			$defaultConnectionName = $this->funcs->_config('database.default');
			$defaultConnectionConfig = $wpspDatabaseConnections[$defaultConnectionName];
			$this->capsule->addConnection($defaultConnectionConfig, 'default');

			foreach ($wpspDatabaseConnections as $connectionName => $connectionConfig) {
				$this->capsule->addConnection($connectionConfig, $connectionName);
			}

			$this->capsule->setAsGlobal();
			$this->capsule->bootEloquent();
		}
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
		$this->funcs->_getAppEloquent()->getCapsule()->schema()->withoutForeignKeyConstraints(function() use ($tableName) {
			$this->getCapsule()->schema()->dropIfExists($tableName);
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