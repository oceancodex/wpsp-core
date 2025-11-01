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

		$this->extraParams = $extraParams;
		$this->funcs       = $extraParams['funcs'] ?? null;
		$this->output      = $extraParams['output'] ?? null;

		require_once $this->funcs->_getSitePath('/wp-load.php');

		if (!$this->capsule) {
			$this->capsule = new Capsule();

			$this->capsule->getDatabaseManager()->extend('mongodb', function($config, $name) {
				$config['name'] = $name;
				if (class_exists('MongoDB\Laravel\Connection')) {
					return new \MongoDB\Laravel\Connection($config);
				}
				elseif (class_exists('Jenssegers\Mongodb\Connection')) {
					return new \Jenssegers\Mongodb\Connection($config);
				}
				return null;
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
				$this->output->writeln('<fg=green>> [✓] Seeded: ' . $name . ' (' . $runTime . 'ms)  </>');
			}
			static::$called[] = $class;
		}
		return $this;
	}

	protected function resolve($class) {
		if (isset($this->container)) {
			$instance = $this->container->make($class);

			$instance->setContainer($this->container);
		} else {
			$instance = new $class(
				$this->mainPath,
				$this->rootNamespace,
				$this->prefixEnv,
				$this->extraParams
			);
		}

		if (isset($this->command)) {
			$instance->setCommand($this->command);
		}

		return $instance;
	}

}