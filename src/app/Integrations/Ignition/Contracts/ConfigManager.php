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
		return function_exists('update_option') && update_option($this->app->getNamespace().'_ignition_options', $options);
	}

	public function getPersistentInfo(): array {
		return function_exists('get_option') ? get_option($this->app->getNamespace() . '_ignition_options', []) : [];
	}

}