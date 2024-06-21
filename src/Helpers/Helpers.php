<?php

use Carbon\Carbon;
use Illuminate\Container\Container;
use OCBPCORE\View\Blade;

if (!function_exists('public_path')) {
	function public_path($path = null): string {
		return OCBP_PUBLIC_PATH . '/'. ltrim($path, '/');
	}
}

if (!function_exists('asset')) {
	function asset($path, $secure = null): string {
		return OCBP_PUBLIC_URL . '/'. ltrim($path, '/');
	}
}

if (!function_exists('app')) {
	function app($abstract = null, array $parameters = []) {
		if (is_null($abstract)) {
			return Container::getInstance();
		}

		return Container::getInstance()->make($abstract, $parameters);
	}
}

if (!function_exists('env')) {
	function env($var, $default = null): string {
		return \OCBPCORE\Objects\Env\Env::get($var, $default);
	}
}

if (!function_exists('view')) {
	function view($viewName, $data = [], $mergeData = []): \Illuminate\Contracts\View\View {
		if (!Blade::$BLADE) {
			$views        = OCBP_RESOURCES_PATH . '/views';
			$cache        = OCBP_STORAGE_PATH . '/framework/views';
			Blade::$BLADE = new Blade([$views], $cache);
		}
		global $notice;
		Blade::$BLADE->view()->share(['notice' => $notice]);
		return Blade::$BLADE->view()->make($viewName, $data, $mergeData);
	}
}

if (!function_exists('trans')) {
	function trans($string, $wordpress = false) {
		if ($wordpress) {
			return __($string, OCBP_TEXT_DOMAIN);
		}
		else {
			global $translator;
			if (!$translator) {
				$translationPath   = OCBP_RESOURCES_PATH . '/lang';
				$translationLoader = new \Illuminate\Translation\FileLoader(new \Illuminate\Filesystem\Filesystem, $translationPath);
				$translator        = new \Illuminate\Translation\Translator($translationLoader, config('app.locale'));
			}
			return $translator->has($string) ? $translator->get($string) : $translator->get($string, [], config('app.fallback_locale'));
		}
	}
}

if (!function_exists('config')) {
	function config($key) {
		try {
			$configs = [];
			$files   = _getAllFilesInFolder(OCBP_CONFIG_PATH);
			foreach ($files as $file) {
				$configKey        = $file['relative_path'];
				$configKey        = preg_replace('/\.php/iu', '', $configKey);
				$configItemNested = _explodeToNestedArray('/', $configKey, \Noodlehaus\Config::load($file['real_path'])->all());
				$configs          = array_merge_recursive($configs, $configItemNested);
			}
			$configs = new \Dflydev\DotAccessData\Data($configs);
			return $configs->get($key);
		}
		catch (\Exception $e) {
			if (env('APP_DEBUG') == true || env('APP_DEBUG') == 'true') {
				_debug($e->getMessage(), true);
			}
		}
		return null;
	}
}

/*
 *
 */

if (!function_exists('_debug')) {
	function _debug($message = '', $print = false, bool $varDump = false): void {

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
			echo '<pre>'; print_r($message); echo '</pre>';
		}
		else {
			error_log(print_r($message, true));
		}

	}
}

if (!function_exists('_notice')) {
	function _notice($message = '', $type = 'info', $dismiss = true): void {
		global $notice;
		$notice = view('modules.web.admin-pages.common.notice')->with([
			'type'    => $type,
			'message' => $message,
		])->render();
	}
}

if (!function_exists('_response')) {
	function _response($success = false, $data = [], $message = '', $code = 204): array {
		return [
			'success' => $success,
			'message' => $message,
			'data'    => $data,
			'code'    => $code,
		];
	}
}

/*
 *
 */

if (!function_exists('_locale')) {
	function _locale(): string {
		if (function_exists('get_locale')) {
			return get_locale();
		}
		else {
			return env('APP_LOCALE', 'en');
		}
	}
}

if (!function_exists('_build_url')) {
	function _build_url($baseURL, $args): string {
		return add_query_arg($args, $baseURL);
	}
}

if (!function_exists('_dbTableName')) {
	function _dbTableName($name): string {
		return _dbTablePrefix() . $name;
	}
}

if (!function_exists('_dbCMTableName')) {
	function _dbCMTableName($name): string {
		return _dbTablePrefix() . 'cm_' . $name;
	}
}

if (!function_exists('_dbTablePrefix')) {
	function _dbTablePrefix(): string {
		return ($wpdb->prefix ?? 'wp_') . env('APP_SHORT_NAME') . '_';
	}
}

if (!function_exists('_dbCMTablePrefix')) {
	function _dbCMTablePrefix(): string {
		return _dbTablePrefix() . 'cm_';
	}
}

if (!function_exists('_objectToArray')) {
	function _objectToArray($object): array {
		if (is_object($object)) {
			$config        = new \GeneratedHydrator\Configuration(get_class($object));
			$hydratorClass = $config->createFactory()->getHydratorClass();
			$hydrator      = new $hydratorClass();
			return $hydrator->extract($object);
		}
		return [];
	}
}

if (!function_exists('_getPathFromDir')) {
	function _getPathFromDir($targetDir, $path): array|string|null {
		return preg_replace('/^(.*?)' . $targetDir . '(.*?)$/iu', $targetDir . '$2', $path);
	}
}

if (!function_exists('_getAllClassesInDir')) {
	function _getAllClassesInDir(string $namespace = __NAMESPACE__, string $path = __DIR__): array {
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
}

/*
 *
 */

if (!function_exists('_commentTokens')) {
	function _commentTokens(): array {
		$commentTokens = [T_COMMENT];

		if (defined('T_DOC_COMMENT')) {
			$commentTokens[] = T_DOC_COMMENT; // PHP 5
		}

		if (defined('T_ML_COMMENT')) {
			$commentTokens[] = T_ML_COMMENT;  // PHP 4
		}
		return $commentTokens;
	}
}

if (!function_exists('_trailingslash')) {
	function _trailingslash($path): string {
		return str_replace('\\', '/', $path);
	}
}

if (!function_exists('_trailingslashit')) {
	function _trailingslashit($path): string {
		$path = str_replace('\\', '/', $path);
		$path = rtrim($path, '/\\');
		return $path . '/';
	}
}

if (!function_exists('_untrailingslashit')) {
	function _untrailingslashit($path): string {
		$path = str_replace('\\', '/', $path);
		return rtrim($path, '/\\');
	}
}

/*
 *
 */

if (!function_exists('_numberFormat')) {
	function _numberFormat(
		$value,
		$precision = 0,
		$endWithZeros = true,
		$locale = 'vi',
		$currencyCode = 'vnd',
		$style = NumberFormatter::DECIMAL,
		$groupingUsed = true
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
}

if (!function_exists('_getArrItemByKeyDots')) {
	function _getArrItemByKeyDots(array $array, string $key) {
		try {
			$configs = new \Dflydev\DotAccessData\Data($array);
			return $configs->get($key) ?? null;
		}
		catch (\Throwable $e) {
			return null;
		}
	}
}

if (!function_exists('_explodeToNestedArray')) {
	function _explodeToNestedArray($delimiter, $key, $value) {
		$keys = explode($delimiter, $key);
		while ($key = array_pop($keys)) {
			$value = [$key => $value];
		}
		return $value;
	}
}

if (!function_exists('_getArrItemByKeyValue')) {
	function _getArrItemByKeyValue(array $arr, $key, $value = null, $operator = 'equals', $single = true) {
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
}

if (!function_exists('_getAllFileNameInFolder')) {
	function _getAllFilesInFolder(string $path = __DIR__): array {
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
}

if (!function_exists('_dateFormatDiffForHumans')) {
	function _dateDiffForHumans($dateString, $format = 'H:i:s - d/m/Y'): string {
		try {
			return Carbon::createFromFormat($format, $dateString, wp_timezone_string())->locale(get_locale())->diffForHumans();
		}
		catch (\Throwable $e) {
			return trans('messages.undefined');
		}
	}
}