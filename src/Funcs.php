<?php

namespace WPSPCORE;

use Carbon\Carbon;
use NumberFormatter;
use WPSPCORE\Database\Eloquent;
use WPSPCORE\Environment\Environment;
use WPSPCORE\Finder\Finder;
use WPSPCORE\Migration\Migration;
use WPSPCORE\View\Blade;

class Funcs {

	public ?string $mainPath      = null;
	public ?string $rootNamespace = null;
	public ?string $prefixEnv     = null;


	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null) {
		if ($mainPath) $this->mainPath = $mainPath;
		if ($rootNamespace) $this->rootNamespace = $rootNamespace;
		if ($prefixEnv) $this->prefixEnv = $prefixEnv;
	}

	/*
	 *
	 */

	public function _getMainPath($path = null): string {
		return rtrim($this->mainPath, '/\\') . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getRootNamespace(): ?string {
		return $this->rootNamespace;
	}

	public function _getPrefixEnv() {
		return $this->prefixEnv;
	}

	/*
	 *
	 */

	public function _getAppShortName(): ?string {
		return $this->_env('APP_SHORT_NAME', true);
	}

	/**
	 * @return Eloquent
	 */
	public function _getAppEloquent(): Eloquent {
		$globalEloquent = $this->_getAppShortName() . '_eloquent';
		global ${$globalEloquent};
		return ${$globalEloquent};
	}

	/**
	 * @return Migration
	 */
	public function _getAppMigration(): ?Migration {
		$globalMigration = $this->_getAppShortName() . '_migration';
		global ${$globalMigration};
		return ${$globalMigration};
	}

	public function _getMainBaseName(): string {
		return basename($this->_getMainPath());
	}

	public function _getSitePath(): string {
		if (defined('WP_CONTENT_DIR')) {
			$path = WP_CONTENT_DIR;
			$path = preg_replace('/wp-content$/iu', '', $path);
		}
		else {
			$path = $this->_getMainPath();
			$path = preg_replace('/^(.+?)wp-content(.+?)$/iu', '$1', $path);
		}
		$path = rtrim($path, '/\\');
		return $path;
	}

	public function _getMainFilePath(): string {
		return $this->_getMainPath() . '/main.php';
	}

	public function _getAppPath($path = null): string {
		return $this->_getMainPath() . '/app' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getControllerPath(): string {
		return $this->_getAppPath() . '/Http/Controllers';
	}

	public function _getConfigPath(): string {
		return $this->_getMainPath() . '/config';
	}

	public function _getRoutesPath(): string {
		return $this->_getMainPath() . '/routes';
	}

	public function _getResourcesPath($path = null): string {
		return $this->_getMainPath() . '/resources' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getStoragePath($path = null): string {
		return $this->_getMainPath() . '/storage' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getDatabasePath(): string {
		return $this->_getMainPath() . '/database';
	}

	public function _getMigrationPath(): string {
		return $this->_getDatabasePath() . '/migrations';
	}

	public function _getMainUrl(): string {
		if (!function_exists('plugin_dir_url')) {
			require($this->_getSitePath() . '/wp-admin/includes/plugin.php');
		}
		return rtrim(plugin_dir_url($this->_getMainFilePath()), '/\\');
	}

	public function _getPublicUrl(): string {
		return $this->_getMainUrl() . '/public';
	}

	public function _getPublicPath($path = null): string {
		return $this->_getMainPath() . '/public' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getPluginData(): array {
		if (!function_exists('get_plugin_data')) {
			require($this->_getSitePath() . '/wp-admin/includes/plugin.php');
		}
		return get_plugin_data($this->_getMainFilePath());
	}

	public function _getVersion(): string {
		return $this->_getPluginData()['Version'];
	}

	public function _getTextDomain(): string {
		return $this->_getPluginData()['TextDomain'];
	}

	public function _getRequiresPhp(): string {
		return $this->_getPluginData()['RequiresPHP'];
	}

	/*
	 *
	 */

	public function _getAllFilesInFolder(string $path): array {
		$finder = new Finder();
		$finder->files()->in($path);
		foreach ($finder as $file) {
			$files[] = [
				'name_without_extension' => $file->getFilenameWithoutExtension(),
				'real_path'              => $file->getRealPath(),
				'relative_path'          => preg_replace('/\\\/iu', '/', $file->getRelativePathname()),
			];
		}
		return $files ?? [];
	}

	public function _getDBTablePrefix($withWpdbPrefix = true): string {
		if ($withWpdbPrefix) {
			global $wpdb;
			return ($wpdb->prefix ?? 'wp_') . $this->_env('DB_TABLE_PREFIX', true);
		}
		else {
			return $this->_env('DB_TABLE_PREFIX', true);
		}
	}

	public function _getDBCustomMigrationTablePrefix(): string {
		return $this->_getDBTablePrefix() . 'cm_';
	}

	public function _getDBTableName($name): string {
		return $this->_getDBTablePrefix() . $name;
	}

	public function _getDBCustomMigrationTableName($name): string {
		return $this->_getDBTablePrefix() . 'cm_' . $name;
	}

	public function _getPathFromDir($targetDir, $path) {
		return preg_replace('/^(.*?)' . $targetDir . '(.*?)$/iu', $targetDir . '$2', $path);
	}

	public function _getAllClassesInDir(string $namespace = __NAMESPACE__, string $path = __DIR__): array {
		$finder = new \Symfony\Component\Finder\Finder();
		$finder->files()->in($path)->name('*.php');
		foreach ($finder as $file) {
			$className = rtrim($namespace, '\\') . '\\' . $file->getFilenameWithoutExtension();
			if (class_exists($className) && $className !== __CLASS__) {
				try {
					$classes[] = $className;
				}
				catch (\Throwable $e) {
					continue;
				}
			}
		}

		return $classes ?? [];
	}

	public function _getArrItemByKeyDots(array $array, string $key) {
		try {
			$configs = new \Dflydev\DotAccessData\Data($array);
			return $configs->get($key) ?? null;
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	public function _getArrItemByKeyValue(array $arr, $key, $value = null, $operator = 'equals', $single = true) {
		try {
			$result = [];
			foreach ($arr as $item) {
				if ($value) {
					if ($operator == 'equals') {
						if (isset($item[$key]) && $item[$key] == $value) {
							if ($single) {
								$result = $item;
								break;
							}
							else {
								$result[] = $item;
							}
						}
					}
					elseif ($operator == 'contains') {
						if (isset($item[$key]) && preg_match('/' . $value . '/iu', $item[$key])) {
							if ($single) {
								$result = $item;
								break;
							}
							else {
								$result[] = $item;
							}
						}
					}
				}
				else {
					if (isset($item[$key])) {
						if ($single) {
							$result = $item;
							break;
						}
						else {
							$result[] = $item;
						}
					}
				}
			}
			return $result;
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	public function _commentTokens(): array {
		$commentTokens = [T_COMMENT];

		if (defined('T_DOC_COMMENT')) {
			$commentTokens[] = T_DOC_COMMENT; // PHP 5
		}

		if (defined('T_ML_COMMENT')) {
			$commentTokens[] = T_ML_COMMENT;  // PHP 4
		}
		return $commentTokens;
	}

	public function _trailingslash($path): string {
		return str_replace('\\', '/', $path);
	}

	public function _trailingslashit($path): string {
		$path = str_replace('\\', '/', $path);
		$path = rtrim($path, '/\\');
		return $path . '/';
	}

	public function _untrailingslashit($path): string {
		$path = str_replace('\\', '/', $path);
		return rtrim($path, '/\\');
	}

	public function _numberFormat($value, $precision = 0, $endWithZeros = true, $locale = 'vi', $currencyCode = 'vnd', $style = NumberFormatter::DECIMAL, $groupingUsed = true) {
		try {
			if (!$value) return null;
			$formatter = new NumberFormatter($locale, $style);
			$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
			$formatter->setAttribute(NumberFormatter::GROUPING_USED, $groupingUsed);
			if ($style == NumberFormatter::CURRENCY) {
				$formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currencyCode);
			}
			$result = $endWithZeros ? $formatter->format($value) : rtrim($formatter->format($value), '0');
			return preg_replace('/([.,])$/iu', '', $result);
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	public function _explodeToNestedArray($delimiter, $key, $value) {
		$keys = explode($delimiter, $key);
		while ($key = array_pop($keys)) {
			$value = [$key => $value];
		}
		return $value;
	}

	public function _dateDiffForHumans($dateString, $format = 'H:i:s - d/m/Y'): string {
		try {
			return Carbon::createFromFormat($format, $dateString, wp_timezone_string())->locale(get_locale())->diffForHumans();
		}
		catch (\Throwable $e) {
			return $this->_trans('messages.undefined');
		}
	}

	public function _prefixArrayKeys(array $array, ?string $prefix = null): array {
		$results = [];
		foreach ($array as $key => $value) {
			$results[$prefix . $key] = $value;
		}
		return $results;
	}

	public function _removePrefixArrayKeys(array $array, ?string $prefix = null): array {
		$results = [];
		foreach ($array as $key => $value) {
			$key           = preg_replace('/' . $prefix . '/iu', '', $key);
			$results[$key] = $value;
		}
		return $results;
	}

	/*
	 *
	 */

	public function _asset($path, $secure = null): string {
		return $this->_getPublicUrl() . '/' . ltrim($path, '/\\');
	}

	public function _view($viewName, $data = [], $mergeData = []) {
		try {
			if (!Blade::$BLADE) {
				$views        = $this->_getResourcesPath('/views');
				$cache        = $this->_getStoragePath('/framework/views');
				Blade::$BLADE = new Blade([$views], $cache);
			}
			$shareVariables = [];
			$shareClass     = '\\' . $this->_getRootNamespace() . '\\app\\View\\Share';
			$shareVariables = array_merge($shareVariables, (new $shareClass())->variables());
			global $notice;
			$shareVariables = array_merge($shareVariables, ['notice' => $notice]);
			Blade::$BLADE->view()->share($shareVariables);
			return Blade::$BLADE->view()->make($viewName, $data, $mergeData);
		}
		catch (\Exception|\Throwable $e) {
			return '<div class="wrap"><div class="notice notice-error"><p>' . $e->getMessage() . '</p></div></div>';
		}
	}

	public function _trans($string, $wordpress = false) {
		try {
			if ($wordpress) {
				return __($string, $this->_getTextDomain());
			}
			else {
				global $translator;
				if (!$translator) {
					$translationPath   = $this->_getResourcesPath() . '/lang';
					$translationLoader = new \WPSPCORE\Translation\FileLoader(new \Illuminate\Filesystem\Filesystem, $translationPath);
					$translator        = new \WPSPCORE\Translation\Translator($translationLoader, $this->_config('app.locale'));
				}
				return $translator->has($string) ? $translator->get($string) : $translator->get($string, [], $this->_config('app.fallback_locale'));
			}
		}
		catch (\Exception|\Throwable $e) {
			return $string;
		}
	}

	public function _config($key = null, $default = null) {
		try {
			$configs = [];
			$files   = $this->_getAllFilesInFolder($this->_getMainPath() . '/config');
			foreach ($files as $file) {
				$configKey        = $file['relative_path'];
				$configKey        = preg_replace('/\.php/iu', '', $configKey);
				$configItemNested = $this->_explodeToNestedArray('/', $configKey, \Noodlehaus\Config::load($file['real_path'])->all());
				$configs          = array_merge_recursive($configs, $configItemNested);
			}
			$configs = new \Dflydev\DotAccessData\Data($configs);
			return $configs->get($key);
		}
		catch (\Exception $e) {
		}
		return null;
	}

	public function _notice($message = '', $type = 'info', $echo = false, $wrap = false, $class = null, $dismiss = true): void {
		global $notice;
		$notice = '<div class="notice ' . $class . ' notice-' . $type . ' is-dismissible"><p>' . $message . '</p></div>';
		if ($wrap) {
			$notice = '<div class="wrap">' . $notice . '</div>';
		}
		if ($echo) echo $notice;
	}

	public function _buildUrl($baseUrl = null, $args = []): string {
		return add_query_arg($args ?? [], $baseUrl ?? '');
	}

	public function _nonceName($name = null): string {
		return $this->_env('APP_SHORT_NAME', true) . ($name ? '_' . $name : '') . '_nonce';
	}

	/*
	 *
	 */

	public function _env($var, $addPrefix = false, $default = null): ?string {
		return Environment::get($addPrefix ? $this->_getPrefixEnv() . $var : $var, $default);
	}

	public function _debug($message = '', $print = false, bool $varDump = false): void {

		// If "var_dump" mode is OFF.
		if ($varDump) {

			// Start buffer capture.
			ob_start();

			// Dump the values.
			var_dump($message);

			// Put the buffer into a variable.
			$message = ob_get_contents();

			// End capture.
			ob_end_clean();

			// Error log the message.

		}

		if ($print) {
			echo '<pre>';
			print_r($message);
			echo '</pre>';
		}
		else {
			error_log(print_r($message, true));
		}

	}

	public function _locale(): string {
		if (function_exists('get_locale')) {
			return get_locale();
		}
		else {
			return $this->_env('APP_LOCALE', true, 'en');
		}
	}

	public function _response($success = false, $data = [], $message = '', $code = 204): array {
		return [
			'success' => $success,
			'message' => $message,
			'data'    => $data,
			'code'    => $code,
		];
	}

}