<?php

namespace WPSPCORE;

use Carbon\Carbon;
use NumberFormatter;
use WPSPCORE\Environment\Environment;
use WPSPCORE\View\Blade;

class Funcs {

	protected ?string $mainPath      = null;
	protected ?string $rootNamespace = null;
	protected ?string $prefixEnv     = null;


	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null) {
		if ($mainPath) $this->mainPath = $mainPath;
		if ($rootNamespace) $this->rootNamespace = $rootNamespace;
		if ($prefixEnv) $this->prefixEnv = $prefixEnv;
	}

	/*
	 *
	 */

	public function _getMainPath(): string {
		return trim($this->mainPath, '/ \\');
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
		$path = trim($path, '/ \\');
		return $path;
	}

	public function _getMainFilePath(): string {
		return $this->_getMainPath() . '/main.php';
	}

	public function _getAppPath(): string {
		return $this->_getMainPath() . '/app';
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

	public function _getResourcesPath(): string {
		return $this->_getMainPath() . '/resources';
	}

	public function _getStoragePath(): string {
		return $this->_getMainPath() . '/storage';
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
		return trim(plugin_dir_url($this->_getMainFilePath()), '/');
	}

	public function _getPublicUrl(): string {
		return $this->_getMainUrl() . '/public';
	}

	public function _getPublicPath($path = null): string {
		return $this->_getMainPath() . '/public' . ($path ? '/' . ltrim($path, '/') : '');
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
		$finder = new \Symfony\Component\Finder\Finder();
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

	public function _getDBTablePrefix(): string {
		global $wpdb;
		return ($wpdb->prefix ?? 'wp_') . $this->_env('DB_TABLE_PREFIX', true);
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

	public function _getPathFromDir($targetDir, $path): array|string|null {
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

	public function _convertObjectToArray($object): array {
		if (is_object($object)) {
			$config        = new \GeneratedHydrator\Configuration(get_class($object));
			$hydratorClass = $config->createFactory()->getHydratorClass();
			$hydrator      = new $hydratorClass();
			return $hydrator->extract($object);
		}
		return [];
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

	public function _numberFormat($value, $precision = 0, $endWithZeros = true, $locale = 'vi', $currencyCode = 'vnd', $style = NumberFormatter::DECIMAL, $groupingUsed = true): array|string|null {
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

	/*
	 *
	 */

	public function _asset($path, $secure = null): string {
		return $this->_getPublicUrl() . '/' . ltrim($path, '/');
	}

	public function _view($viewName, $data = [], $mergeData = []): \Illuminate\Contracts\View\View {
		if (!Blade::$BLADE) {
			$views        = $this->_getResourcesPath() . '/views';
			$cache        = $this->_getStoragePath() . '/framework/views';
			Blade::$BLADE = new Blade([$views], $cache);
		}
		$shareVariables = [];
		try {
			$shareClass = '\\' . $this->_getRootNamespace() . '\\app\\View\\Share';
			$shareVariables = array_merge($shareVariables, (new $shareClass())->variables());
		}
		catch (\Exception $e) {
		}
		global $notice;
		$shareVariables = array_merge($shareVariables, ['notice' => $notice]);
		Blade::$BLADE->view()->share($shareVariables);
		return Blade::$BLADE->view()->make($viewName, $data, $mergeData);
	}

	public function _trans($string, $wordpress = false) {
		if ($wordpress) {
			return __($string, $this->_getTextDomain());
		}
		else {
			global $translator;
			if (!$translator) {
				$translationPath   = $this->_getResourcesPath() . '/lang';
				$translationLoader = new \Illuminate\Translation\FileLoader(new \Illuminate\Filesystem\Filesystem, $translationPath);
				$translator        = new \Illuminate\Translation\Translator($translationLoader, $this->_config('app.locale'));
			}
			return $translator->has($string) ? $translator->get($string) : $translator->get($string, [], $this->_config('app.fallback_locale'));
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

	public function _notice($message = '', $type = 'info', $echo = false, $class = null, $dismiss = true): void {
		global $notice;
		$notice = '<div class="notice ' . $class . ' notice-' . $type . ' is-dismissible"><p>' . $message . '</p></div>';
		if ($echo) echo $notice;
	}

	public function _buildUrl($baseUrl, $args): string {
		return add_query_arg($args, $baseUrl);
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