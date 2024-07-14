<?php

namespace WPSPCORE\Base;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Output\Output;
use WPSPCORE\Funcs;

abstract class BaseSeeder extends Seeder {

	public ?string  $mainPath      = null;
	public ?string  $rootNamespace = null;
	public ?string  $prefixEnv     = null;
	public ?Funcs   $funcs         = null;
	public ?Capsule $capsule       = null;
	private ?Output $output        = null;

	public function __construct($output = null) {
		$this->output = $output;
		$this->beforeInstanceConstruct();
		$this->funcs = new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv);
//		if (!$this->capsule) {
//			$databaseConfig = include($this->funcs->_getMainPath() . '/config/database.php');
//			echo '<pre style="z-index: 9999; position: relative; clear: both;">'; print_r($databaseConfig); echo '</pre>';
//			$this->capsule  = new Capsule();
//			$this->capsule->addConnection($databaseConfig);
//			$this->capsule->setAsGlobal();
//			$this->capsule->bootEloquent();
//		}
	}

	public function call($class, $silent = false, array $parameters = []): static {
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

	protected function beforeInstanceConstruct() {}

}