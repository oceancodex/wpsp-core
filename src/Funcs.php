<?php

namespace WPSPCORE;

use Carbon\Carbon;
use NumberFormatter;
use WPSPCORE\View\Blade;

class Funcs {

	protected ?string $mainPath      = null;
	protected ?string $rootNamespace = null;
	protected ?string $envKeyPrefix  = null;

	public function __construct($mainPath = null, $rootNamespace = null, $envKeyPrefix = null) {
		if ($mainPath) $this->mainPath = $mainPath;
		if ($rootNamespace) $this->rootNamespace = $rootNamespace;
		if ($envKeyPrefix) $this->envKeyPrefix = $envKeyPrefix;
	}

	/*
	 *
	 */

	public function getRootNamespace(): ?string {
		return $this->rootNamespace;
	}

	public function getMainPath(): string {
		return trim($this->mainPath, '/ \\');
	}

	public function getMainBaseName(): string {
		return basename(self::getMainPath());
	}

	public function getSitePath(): string {
		if (defined('WP_CONTENT_DIR')) {
			$path = WP_CONTENT_DIR;
			$path = preg_replace('/wp-content$/iu', '', $path);
		}
		else {
			$path = self::getMainPath();
			$path = preg_replace('/^(.+?)wp-content(.+?)$/iu', '$1', $path);
		}
		$path = trim($path, '/ \\');
		return $path;
	}

	public function getMainFilePath(): string {
		return self::getMainPath() . '/main.php';
	}

	public function getAppPath(): string {
		return self::getMainPath() . '/app';
	}

	public function getControllerPath(): string {
		return self::getAppPath() . '/Http/Controllers';
	}

	public function getConfigPath(): string {
		return self::getMainPath() . '/config';
	}

	public function getRoutesPath(): string {
		return self::getMainPath() . '/routes';
	}

	public function getResourcesPath(): string {
		return self::getMainPath() . '/resources';
	}

	public function getStoragePath(): string {
		return self::getMainPath() . '/storage';
	}

	public function getDatabasePath(): string {
		return self::getMainPath() . '/database';
	}

	public function getMigrationPath(): string {
		return self::getDatabasePath() . '/migrations';
	}

	public function getMainUrl(): string {
		if (!function_exists('plugin_dir_url')) {
			require(self::getSitePath() . '/wp-admin/includes/plugin.php');
		}
		return trim(plugin_dir_url(self::getMainFilePath()), '/');
	}

	public function getPublicUrl(): string {
		return self::getMainUrl() . '/public';
	}

	public function getPublicPath($path = null): string {
		return self::getMainPath() . '/public' . ($path ? '/' . ltrim($path, '/') : '');
	}

	public function getPluginData(): array {
		if (!function_exists('get_plugin_data')) {
			require(self::getSitePath() . '/wp-admin/includes/plugin.php');
		}
		return get_plugin_data(self::getMainFilePath());
	}

	public function getVersion(): string {
		return self::getPluginData()['Version'];
	}

	public function getTextDomain(): string {
		return self::getPluginData()['TextDomain'];
	}

	public function getRequiresPhp(): string {
		return self::getPluginData()['RequiresPHP'];
	}

	/*
	 *
	 */

	public function getAllFilesInFolder(string $path): array {
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

	public function getDBTablePrefix(): string {
		global $wpdb;
		return ($wpdb->prefix ?? 'wp_') . $this->env('APP_SHORT_NAME', true) . '_';
	}

	public function getDBCustomMigrationTablePrefix(): string {
		return $this->getDBTablePrefix() . 'cm_';
	}

	public function getDBTableName($name): string {
		return $this->getDBTablePrefix() . $name;
	}

	public function getDBCustomMigrationTableName($name): string {
		return $this->getDBTablePrefix() . 'cm_' . $name;
	}

	public function getPathFromDir($targetDir, $path): array|string|null {
		return preg_replace('/^(.*?)' . $targetDir . '(.*?)$/iu', $targetDir . '$2', $path);
	}

	public function getAllClassesInDir(string $namespace = __NAMESPACE__, string $path = __DIR__): array {
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

	public function getArrItemByKeyDots(array $array, string $key) {
		try {
			$configs = new \Dflydev\DotAccessData\Data($array);
			return $configs->get($key) ?? null;
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	public function convertObjectToArray($object): array {
		if (is_object($object)) {
			$config        = new \GeneratedHydrator\Configuration(get_class($object));
			$hydratorClass = $config->createFactory()->getHydratorClass();
			$hydrator      = new $hydratorClass();
			return $hydrator->extract($object);
		}
		return [];
	}

	public function commentTokens(): array {
		$commentTokens = [T_COMMENT];

		if (defined('T_DOC_COMMENT')) {
			$commentTokens[] = T_DOC_COMMENT; // PHP 5
		}

		if (defined('T_ML_COMMENT')) {
			$commentTokens[] = T_ML_COMMENT;  // PHP 4
		}
		return $commentTokens;
	}

	public function trailingslash($path): string {
		return str_replace('\\', '/', $path);
	}

	public function trailingslashit($path): string {
		$path = str_replace('\\', '/', $path);
		$path = rtrim($path, '/\\');
		return $path . '/';
	}

	public function untrailingslashit($path): string {
		$path = str_replace('\\', '/', $path);
		return rtrim($path, '/\\');
	}

	public function numberFormat(
		$value,
		$precision = 0,
		$endWithZeros = true,
		$locale = 'vi',
		$currencyCode = 'vnd',
		$style = NumberFormatter::DECIMAL,
		$groupingUsed = true,
	): array|string|null {
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

	public function explodeToNestedArray($delimiter, $key, $value) {
		$keys = explode($delimiter, $key);
		while ($key = array_pop($keys)) {
			$value = [$key => $value];
		}
		return $value;
	}

	public function dateDiffForHumans($dateString, $format = 'H:i:s - d/m/Y'): string {
		try {
			return Carbon::createFromFormat($format, $dateString, wp_timezone_string())->locale(get_locale())->diffForHumans();
		}
		catch (\Throwable $e) {
			return trans('messages.undefined');
		}
	}

	/*
	 *
	 */

	public function asset($path, $secure = null): string {
		return $this->getPublicUrl() . '/' . ltrim($path, '/');
	}

	public function view($viewName, $data = [], $mergeData = []): \Illuminate\Contracts\View\View {
		if (!Blade::$BLADE) {
			$views        = $this->getResourcesPath() . '/views';
			$cache        = $this->getStoragePath() . '/framework/views';
			Blade::$BLADE = new Blade([$views], $cache);
		}
		global $notice;
		Blade::$BLADE->view()->share(['notice' => $notice]);
		return Blade::$BLADE->view()->make($viewName, $data, $mergeData);
	}

	public function config($key = null, $default = null) {
		try {
			$configs = [];
			$files   = self::getAllFilesInFolder($this->getMainPath() . '/config');
			foreach ($files as $file) {
				$configKey        = $file['relative_path'];
				$configKey        = preg_replace('/\.php/iu', '', $configKey);
				$configItemNested = $this->explodeToNestedArray('/', $configKey, \Noodlehaus\Config::load($file['real_path'])->all());
				$configs          = array_merge_recursive($configs, $configItemNested);
			}
			$configs = new \Dflydev\DotAccessData\Data($configs);
			return $configs->get($key);
		}
		catch (\Exception $e) {
		}
		return null;
	}

	public function trans($string, $wordpress = false) {
		if ($wordpress) {
			return __($string, $this->getTextDomain());
		}
		else {
			global $translator;
			if (!$translator) {
				$translationPath   = $this->getResourcesPath() . '/lang';
				$translationLoader = new \Illuminate\Translation\FileLoader(new \Illuminate\Filesystem\Filesystem, $translationPath);
				$translator        = new \Illuminate\Translation\Translator($translationLoader, $this->config('app.locale'));
			}
			return $translator->has($string) ? $translator->get($string) : $translator->get($string, [], $this->config('app.fallback_locale'));
		}
	}

	public function notice($message = '', $type = 'info', $dismiss = true): void {
		global $notice;
		$notice = $this->view('modules.web.admin-pages.common.notice')->with([
			'type'    => $type,
			'message' => $message,
		])->render();
	}

	public function buildUrl($baseUrl, $args): string {
		return add_query_arg($args, $baseUrl);
	}

	/*
	 *
	 */

	public function env($var, $addPrefix = false, $default = null): ?string {
		return \WPSPCORE\Environment\Environment::get($addPrefix ? $this->envKeyPrefix . $var : $var, $default);
	}

	public function debug($message = '', $print = false, bool $varDump = false): void {

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

	public function response($success = false, $data = [], $message = '', $code = 204): array {
		return [
			'success' => $success,
			'message' => $message,
			'data'    => $data,
			'code'    => $code,
		];
	}

	public function locale(): string {
		if (function_exists('get_locale')) {
			return get_locale();
		}
		else {
			return $this->env('APP_LOCALE', true, 'en');
		}
	}

}