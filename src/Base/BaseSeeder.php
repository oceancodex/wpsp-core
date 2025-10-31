<?php

namespace WPSPCORE\Base;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use WPSPCORE\Funcs;
use WPSPCORE\Traits\BaseInstancesTrait;

/**
 * @property \WPSPCORE\Funcs                               $funcs
 * @property Capsule|null                                  $capsule
 * @property \Symfony\Component\Console\Output\Output|null $output
 */
abstract class BaseSeeder extends Seeder {

	use BaseInstancesTrait;

	public $mainPath      = null;
	public $rootNamespace = null;
	public $prefixEnv     = null;

	public $funcs         = null;
//	public $validation    = null;
//	public $environment   = null;
//	public $extraParams   = [];

	public $capsule       = null;
	public $output        = null;

	public static $called = [];

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		$this->mainPath      = $mainPath;
		$this->rootNamespace = $rootNamespace;
		$this->prefixEnv     = $prefixEnv;

//		$this->output      = $extraParams['output'] ?? null;
		$this->funcs       = $extraParams['funcs'] ?? null;

		unset($this->funcs->request);
		unset($this->funcs->validation);

//		$this->environment = $extraParams['environment'] ?? null;
//		$this->validation  = $extraParams['validation'] ?? null;

		require_once $this->funcs->_getSitePath('/wp-includes/pluggable.php');

		if (!$this->capsule) {
			$this->capsule = new Capsule();

			$this->capsule->getDatabaseManager()->extend('mongodb', function($config, $name) {
				$config['name'] = $name;
				return new \Jenssegers\Mongodb\Connection($config);
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

	/*
	 *
	 */

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

}