<?php

namespace WPSPCORE\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\Console\Traits\CommandsTrait;
use WPSPCORE\FileSystem\FileSystem;

class RouteRemapCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('route:remap')
			->setDescription('Remap routes.                             | Eg: bin/wpsp route:remap')
			->setHelp('This command is used to remap routes...')
			->addOption('ide', null, InputOption::VALUE_OPTIONAL, 'Choose IDE to auto-reload. Supported: phpstorm')
			->addOption('ignore-active-plugin', null, InputOption::VALUE_OPTIONAL, 'Ignore active plugin or not.');
	}

	protected function execute(InputInterface $input, OutputInterface $output, $ignoreActivePlugin = false): int {
		$ignoreActivePlugin = $ignoreActivePlugin || $input->getOption('ignore-active-plugin');

		if ($ignoreActivePlugin) {
			$wpConfig = $this->funcs->_getWPConfig();
			$host     = $wpConfig['DB_HOST'] ?? $this->funcs->_env('DB_HOST', true) ?? null;
			$user     = $wpConfig['DB_USER'] ?? $this->funcs->_env('WPSP_DB_USERNAME', true) ?? null;
			$password = $wpConfig['DB_PASSWORD'] ?? $this->funcs->_env('DB_PASSWORD', true) ?? null;

			if ($host) {
				try {
					$test = @mysqli_connect($host, $user, $password);
					if (!$test) {
						$this->writeln($output, '<red>Unable to connect to database, please check your wp-config.php or .env to make sure the database connection information is declared correctly.</red>');
						return Command::FAILURE;
					}
				}
				catch (\Throwable $e) {
					$this->writeln($output, '<red>Database server not found. Please make sure your database server is running and the database connection information in wp-config.php or .env is correct.</red>');
					return Command::FAILURE;
				}
			}
			else {
				$this->writeln($output, '<red>WP Config not found or database connection information in .env file is not configured.</red>');
				return Command::FAILURE;
			}

			require $this->funcs->_getSitePath('/wp-config.php');

			// Prepare route map.
			$routeMap = $this->funcs->getRouteMap()->getMapIdea() ?? [];

			if (empty($routeMap)) {
				$this->writeln($output, '<error>No routes found!</error>');
				$this->writeln($output, '<info>You must make sure that your Database Server is running.</info>');
				return Command::FAILURE;
			}

			$pluginDirName = $this->funcs->_getPluginDirName();

			$prepareMap           = [];
			$prepareMap['scope']  = $pluginDirName;
			$prepareMap['routes'] = $routeMap;
			$prepareMap           = json_encode($prepareMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
//		    $prepareMap = json_encode($prepareMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

			// Write file.
			FileSystem::put($this->mainPath . '/.wpsp-routes.json', $prepareMap);

			// Handle IDE auto-reload
			$ide = strtolower($input->getOption('ide') ?? null);
			if ($ide === 'phpstorm') {
				$this->writeln($output, '[IDE] Auto reload triggered for PHPStorm');
				$psScript = $this->funcs->_getMainPath('/bin/phpstorm-auto-reload.ps1');
				exec('pwsh ' . escapeshellarg($psScript));
			}

			// Output message.
			$this->writeln($output, '<green>Remap routes successfully!</green>');

			// this method must return an integer number with the "exit status code"
			// of the command. You can also use these constants to make code more readable

			// return this if there was no problem running the command
			// (it's equivalent to returning int(0))
			return Command::SUCCESS;

			// or return this if some error happened during the execution
			// (it's equivalent to returning int(1))
//		    return Command::FAILURE;

			// or return this to indicate incorrect command usage; e.g. invalid options
			// or missing arguments (it's equivalent to returning int(2))
			// return Command::INVALID
		}
		else {
			$pluginActivated = $this->maybeActivePlugin($this->funcs->_getMainBaseName() . '/main.php', $output);
			if ($pluginActivated === true) {
				passthru('php bin/wpsp route:remap --ignore-active-plugin=true 2>&1');
			}
			elseif ($pluginActivated === 'activated') {
				$this->execute($input, $output, true);
			}
		}
		return Command::FAILURE;
	}

	protected function maybeActivePlugin(string $plugin, OutputInterface $output = null) {
		try {
			$pluginName = dirname($plugin);
			$pluginSlug = $plugin;

			if ($this->isPluginActiveFast($this->funcs->_getMainBaseName() . '/main.php')) {
				return 'activated';
			}

			$this->writeln($output, '<yellow>Load WordPress...</yellow>');

			require_once $this->funcs->_getSitePath('/wp-load.php');

			if (is_plugin_active($pluginSlug)) return 'activated';

			$this->writeln($output, '<yellow>Activating plugin "' . $pluginName . '" ...</yellow>');

//			if (function_exists('current_user_can') && !current_user_can('activate_plugins')) {
//				$this->writeln($output, '<red>No permission to activate plugin: "'.$pluginName.'"</red>');
//				return false;
//			}

			$res = activate_plugin($pluginSlug);
			if (is_wp_error($res)) {
				$this->writeln($output, '<red>Failed to activate plugin: "' . $pluginName . '" - ' . $res->get_error_message() . '</red>');
				return false;
			}

			if (!is_plugin_active($pluginSlug)) {
				$this->writeln($output, '<yellow>Activation succeeded but plugin still inactive: "' . $pluginName . '"</yellow>');
				return false;
			}

			$this->writeln($output, '<green>Plugin "' . $pluginName . '" activated successfully!</green>');

			return true;
		}
		catch (\Throwable $e) {
			$this->writeln($output, '<red>Error when activating plugin "' . $pluginName . '" - ' . $e->getMessage() . '</red>');
			return false;
		}
	}

	protected function isPluginActiveFast(string $plugin): bool {
		try {
			$wpConfig = $this->funcs->_getWPConfig();
			$prefix   = $wpConfig['table_prefix'] ?? 'wp_';
			$host     = $wpConfig['DB_HOST'] ?? null;
			$user     = $wpConfig['DB_USER'] ?? null;
			$password = $wpConfig['DB_PASSWORD'] ?? null;
			$database = $wpConfig['DB_NAME'] ?? null;

			$mysqli = @new \mysqli($host, $user, $password, $database);
			if ($mysqli->connect_error) return false;

			$result = $mysqli->query("SELECT option_value FROM {$prefix}options WHERE option_name='active_plugins' LIMIT 1");
			if (!$result) return false;

			$row    = $result->fetch_assoc();
			$active = @unserialize($row['option_value']);
			return is_array($active) && in_array($plugin, $active, true);
		}
		catch (\Throwable $e) {
			error_log('isPluginActiveFast error: ' . $e->getMessage());
			return false;
		}
	}

}