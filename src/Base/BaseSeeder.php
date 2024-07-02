<?php

namespace WPSPCORE\Base;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;
use WPSPCORE\Funcs;

abstract class BaseSeeder extends Seeder {

	public ?string  $mainPath      = null;
	public ?string  $rootNamespace = null;
	public ?string  $prefixEnv     = null;
	public ?Funcs   $funcs         = null;
	public ?Capsule $capsule       = null;

	public function __construct() {
		$this->beforeInstanceConstruct();
		$this->funcs = new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv);
		if (!$this->capsule) {
			$databaseConfig = include($this->funcs->_getMainPath() . '/config/database.php');
			$this->capsule  = new Capsule();
			$this->capsule->addConnection($databaseConfig);
			$this->capsule->setAsGlobal();
			$this->capsule->bootEloquent();
		}
	}

	public function beforeInstanceConstruct() {}

}