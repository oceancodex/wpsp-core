<?php

namespace WPSPCORE\Base;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use WPSPCORE\Funcs;

abstract class BaseSeeder extends Seeder {

	public $mainPath      = null;
	public $rootNamespace = null;
	public $prefixEnv     = null;
	/** @var Funcs|null */
	public $funcs         = null;

	/** @var Capsule|null */
	public $capsule       = null;
	/** @var \Symfony\Component\Console\Output\Output|null */
	private $output       = null;

	public function __construct($output = null) {
		$this->output = $output;
		$this->beforeConstruct();
//		$this->funcs = new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv, ['prepare_funcs' => false]);
		if (!$this->capsule) {
			$this->capsule = new Capsule();

			$this->capsule->getDatabaseManager()->extend('mongodb', function($config, $name) {
				$config['name'] = $name;
				return new \MongoDB\Laravel\Connection($config);
			});

			$databaseConnections = $this->funcs->_config('database.connections');

			$defaultConnectionName   = $this->funcs->_getAppShortName() . '_' . $this->funcs->_config('database.default');
			$defaultConnectionConfig = $databaseConnections[$defaultConnectionName];
			$this->capsule->addConnection($defaultConnectionConfig);

			foreach ($databaseConnections as $connectionName => $connectionConfig) {
				$this->capsule->addConnection($connectionConfig, $connectionName);
			}

			$this->capsule->setAsGlobal();
			$this->capsule->bootEloquent();
		}
	}

	public function call($class, $silent = false, $parameters = []) {
		$classes = Arr::wrap($class);
		foreach ($classes as $class) {
			$seeder    = $this->resolve($class);
			$name      = get_class($seeder);
			$startTime = microtime(true);
			$seeder->__invoke($parameters);
			if ($this->output) {
				$runTime = number_format((microtime(true) - $startTime) * 1000);
				$this->output->writeln('<fg=green>[OK] Run seeder: ' . $name . ' (' . $runTime . 'ms)  </>');
			}
			static::$called[] = $class;
		}
		return $this;
	}

	public function beforeConstruct() {}

}