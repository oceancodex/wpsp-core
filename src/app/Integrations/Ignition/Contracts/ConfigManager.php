<?php

namespace WPSPCORE\App\Integrations\Ignition\Contracts;

class ConfigManager implements \Spatie\Ignition\Contracts\ConfigManager {

	/** @var \Illuminate\Foundation\Application */
	public $app;

	public function __construct($app) {
		$this->app = $app;
	}

	public function load(): array {
		return require $this->app->configPath('ignition.php');
	}

	public function save(array $options): bool {
		return false;
	}

	public function getPersistentInfo(): array {
		return [];
	}

}