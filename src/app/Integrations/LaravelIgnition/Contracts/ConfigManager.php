<?php

namespace WPSPCORE\App\Integrations\LaravelIgnition\Contracts;

class ConfigManager implements \Spatie\Ignition\Contracts\ConfigManager {

	/** @var \Illuminate\Foundation\Application */
	public $app;
	public $config;
	public $jsonPath;

	/**
	 * @param \Illuminate\Foundation\Application $app
	 */
	public function __construct($app) {
		$this->app      = $app;
		$this->config   = require $app->configPath('ignition.php');
		$this->jsonPath = ((isset($this->config['settings_file_path']) && $this->config['settings_file_path']) ? $this->config['settings_file_path'] : (($_SERVER['HOME'] ?? sys_get_temp_dir()).'/.ignition.json'));
	}

	public function load(): array {
		return array_merge($this->config, $this->getPersistentInfo());
	}

	public function save(array $options): bool {
		return (bool)file_put_contents(
			$this->jsonPath,
			json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
		);
	}

	public function getPersistentInfo(): array {
		if (!is_file($this->jsonPath)) {
			return [];
		}

		$decoded = json_decode((string)file_get_contents($this->jsonPath), true);

		return is_array($decoded) ? $decoded : [];
	}

}