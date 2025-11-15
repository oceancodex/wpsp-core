<?php

namespace WPSPCORE;

use Carbon\Carbon;
use NumberFormatter;
use WPSPCORE\Base\BaseInstances;

class Funcs extends BaseInstances {

	private $routeMapClass;
	private $WPSPClass;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->WPSPClass = '\\' . $this->rootNamespace . '\WPSP';
		$this->routeMapClass = '\\' . $this->rootNamespace . '\app\Instances\Routes\RouteMap';
	}

	/*
	 *
	 */

	/**
	 * @return \WPSPCORE\Routes\RouteMap
	 */
	public function getRouteMap() {
		try {
			return $this->routeMapClass::instance();
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	public function getApplication($abstract = null, $parameters = []) {
		try {
			if ($abstract) {
				return $this->getWPSP()->getApplication()->make($abstract, $parameters);
			}
			return $this->getWPSP()->getApplication();
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	public function getWPSP() {
		try {
			return $this->WPSPClass::instance();
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	public function getWPSPClass() {
		return $this->WPSPClass;
	}

	/*
	 *
	 */

	public function _getMainPath($path = null): string {
		return rtrim($this->mainPath, '/\\') . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getRootNamespace() {
		return $this->rootNamespace;
	}

	public function _getPrefixEnv() {
		return $this->prefixEnv;
	}

	/*
	 *
	 */

	public function _getBearerToken($request = null): ?string {
		$request = $request ?? $this->getApplication('request') ?? null;

		// --- Lấy raw header ---
		if ($request && method_exists($request, 'headers')) {
			$authHeader = $request->headers->get('Authorization');
		}
		else {
			$headers    = function_exists('getallheaders') ? getallheaders() : [];
			$headers    = array_change_key_case($headers, CASE_LOWER);
			$authHeader = $headers['authorization']
				?? $_SERVER['HTTP_AUTHORIZATION']
				?? $_SERVER['Authorization']
				?? null;
		}

		if (!$authHeader) {
			return null;
		}

		// --- Parse Bearer token ---
		if (preg_match('/Bearer\s+(\S+)/i', trim($authHeader), $matches)) {
			return trim($matches[1]);
		}

		return null;
	}

	public function _getAppShortName() {
		return $this->_env('APP_SHORT_NAME', true);
	}

	public function _getMainBaseName(): string {
		return basename($this->_getMainPath());
	}

	public function _getSitePath($appendPath = null): string {
		if (defined('WP_CONTENT_DIR')) {
			$path = WP_CONTENT_DIR;
			$path = preg_replace('/wp-content$/iu', '', $path);
		}
		else {
			$path = $this->_getMainPath();
			$path = preg_replace('/^(.+?)wp-content(.+?)$/iu', '$1', $path);
		}
		$path = rtrim($path, '/\\');
		if ($appendPath) {
			$path .= '/' . ltrim($appendPath, '/\\');
		}
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

	public function _getVersion() {
		return $this->_getPluginData()['Version'];
	}

	public function _getTextDomain() {
		return $this->_getPluginData()['TextDomain'];
	}

	public function _getRequiresPhp() {
		return $this->_getPluginData()['RequiresPHP'];
	}

	public function _getAllFilesInFolder($path): array {
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

	public function _getDBTablePrefix($withWpdbPrefix = true) {
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

	public function _getAllClassesInDir($namespace = __NAMESPACE__, $path = __DIR__): array {
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

	public function _getArrItemByKeyDots($array, $key) {
		try {
			$configs = new \Dflydev\DotAccessData\Data($array);
			return $configs->get($key) ?? null;
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	public function _getArrItemByKeyValue($arr, $key, $value = null, $operator = 'equals', $single = true) {
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

	public function _getPluginDirName(): string {
		return $this->_getMainBaseName();
	}

	public function _getWPConfig($file = null): array {
		if (!$file) {
			$file = $this->_getSitePath() . '/wp-config.php';
		}

		$defines = [];
		$tokens  = token_get_all(file_get_contents($file));

		$count = count($tokens);
		for ($i = 0; $i < $count; $i++) {

			// Tìm keyword define
			if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING && strtolower($tokens[$i][1]) === 'define') {

				// Kiểm tra dấu mở ngoặc
				$j = $i + 1;
				while ($j < $count && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
					$j++;
				}

				if ($j >= $count || $tokens[$j] !== '(') {
					continue;
				}

				// Lấy tham số đầu tiên (key)
				$j++;
				while ($j < $count && (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE)) {
					$j++;
				}

				if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_CONSTANT_ENCAPSED_STRING) {
					continue;
				}
				$key = trim($tokens[$j][1], "\"'");

				// Tìm dấu phẩy
				do {
					$j++;
				}
				while ($j < $count && $tokens[$j] !== ',');

				if ($j >= $count) continue;

				// Lấy tham số thứ hai (value)
				$j++;
				while ($j < $count && (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE)) {
					$j++;
				}

				if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_CONSTANT_ENCAPSED_STRING) {
					continue;
				}
				$value = trim($tokens[$j][1], "\"'");

				$defines[$key] = $value;
			}
		}

		return $defines;
	}

	/*
	 *
	 */

	public function _app($abstract, $parameters = []) {
		return $this->getApplication($abstract, $parameters);
	}

	public function _env($var, $addPrefix = false, $default = null) {
		$var = $addPrefix ? $this->_getPrefixEnv() . $var : $var;
		if (function_exists('env')) {
			$result = env($var, $default) ?? $default;
		}
		elseif (function_exists('getenv')) {
			$result = getenv($var) ?? $default;
		}
		else {
			$result = $default;
		}
		return $result;
	}

	public function _view($viewName = null, $data = [], $mergeData = [], $instance = false) {
		/** @var \Illuminate\View\Factory $blade */
		$blade = $this->getApplication('view');
		try {
			if (!$viewName && $instance) {
				return $blade ?? null;
			}
			if ($blade !== null) {
				return $blade->make($viewName, $data, $mergeData);
			}
			return null;
		}
		catch (\Throwable $e) {
			return '<div class="wrap"><div class="notice notice-error"><p>' . $e->getMessage() . '</p></div></div>';
		}
	}

	public function _debug($message = '', $print = false, $varDump = false) {

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

	public function _asset($path, $secure = null): string {
		return $this->_getPublicUrl() . '/' . ltrim($path, '/\\');
	}

	public function _route(array $mapIdea, string $routeClass, string $routeName, $args = [], bool $buildURL = false) {
		if (preg_match('/\\\\/', $routeClass)) {
			$routeClass = trim($routeClass, '\\');
			$parts      = explode('\\', $routeClass);
			$routeClass = end($parts);
		}

		$routeFromMap = $mapIdea[$routeClass][$routeName] ?? null;

		if ($routeFromMap) {
			switch ($routeClass) {
				case 'Apis':
					$routeFromMap = $routeFromMap['namespace'] . '/' . $routeFromMap['version'] . '/' . $routeFromMap['path'];
					break;
				default:
					$routeFromMap = $routeFromMap['path'];
			}

			if (!empty($args) && is_array($args)) {
				// Tìm tất cả các placeholder (?P<key>pattern)
				if (preg_match_all('/\(\?P<([^>]+)>[^)]+\)/', $routeFromMap, $matches)) {
					foreach ($matches[1] as $matchIndex => $paramName) {
						if (isset($args[$paramName])) {
							// Thay thế đúng phần placeholder bởi giá trị
							$routeFromMap = preg_replace(
								'/' . preg_quote($matches[0][$matchIndex], '/') . '/',
								rawurlencode($args[$paramName]),
								$routeFromMap,
								1
							);
							unset($args[$paramName]); // Đã xử lý rồi thì bỏ đi
						}
					}
				} else {
					// Nếu không có group tên -> match lần lượt group không tên ()
					if (preg_match_all('/\(([^?][^)]+)\)/', $routeFromMap, $unnamedGroups)) {
						$i = 0;
						foreach ($unnamedGroups[0] as $groupPattern) {
							if (isset($args[$i])) {
								$routeFromMap = preg_replace(
									'/' . preg_quote($groupPattern, '/') . '/',
									rawurlencode($args[$i]),
									$routeFromMap,
									1
								);
							}
							$i++;
						}
						$args = array_slice($args, $i); // Bỏ các args đã thay
					}
				}

				// Nếu còn args chưa mapping vào route thì nối query string như cũ
				if (!empty($args)) {
					$routeFromMap = add_query_arg($args, $routeFromMap);
					$routeFromMap = add_query_arg($args, rawurlencode($routeFromMap));
					$routeFromMap = rawurldecode($routeFromMap);
				}
			}

			// 🧹 Làm sạch regex pattern thừa
			$routeFromMap = preg_replace([
				'/\^\//',      // bỏ ^/
				'/\/?\$$/',    // bỏ /?$
				'/\$$/',       // bỏ $
			], '', $routeFromMap);
			$routeFromMap = preg_replace('/\\\\\//', '/', $routeFromMap);

			if ($buildURL || (is_bool($args) && $args)) {
				switch ($routeClass) {
					case 'Apis':
						$routeFromMap = rest_url($routeFromMap);
						break;
					case 'Ajaxs':
						$routeFromMap = admin_url('admin-ajax.php?action=' . $routeFromMap);
						break;
					case 'AdminPages':
						$routeFromMap = $this->_sanitizeURL(admin_url('admin.php?page=' . $routeFromMap));
						break;
					case 'RewriteFrontPages':
						$routeFromMap = $this->_sanitizeURL(home_url($routeFromMap));
						break;
					default:
				}
			}
		}

		// Bỏ dấu / dư đầu-cuối
		$routeFromMap = trim($routeFromMap, '/');

		return $routeFromMap;
	}

	public function _trans($string, $wordpress = false) {
		try {
			if ($wordpress) {
				return __($string, $this->_getTextDomain());
			}
			else {
				$translation = $this->getApplication('translator');
				return $translation->has($string) ? $translation->get($string) : $translation->get($string, [], $this->_config('app.fallback_locale'));
			}
		}
		catch (\Throwable $e) {
			return $string;
		}
	}

	public function _config($key = null, $default = null) {
		try {
			$config = $this->getApplication('config');
			return $config->get($key);
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	public function _locale() {
		if (function_exists('get_locale')) {
			return get_locale();
		}
		else {
			return $this->_env('APP_LOCALE', true, 'en');
		}
	}

	public function _notice($message = '', $type = 'info', $echo = false, $wrap = false, $class = null, $dismiss = true) {
		global $notice;
		$notice = '<div class="notice ' . $class . ' notice-' . $type . ' is-dismissible"><p>' . $message . '</p></div>';
		if ($wrap) {
			$notice = '<div class="wrap">' . $notice . '</div>';
		}
		if ($echo) {
			error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
			@ini_set('display_errors', 0);
			echo $notice;
		}
	}

	public function _response($success = false, $data = [], $message = ''): array {
		return [
			'success' => $success,
			'data'    => $data,
			'message' => $message,
		];
	}

	public function _viewInject($views, $callback) {
		return $this->_viewInstance()->composer($views, $callback);
	}

	public function _viewInstance() {
		return $this->_view(null, [], [], true);
	}

	/*
	 *
	 */




	public function _buildUrl($baseUrl = null, $args = []) {
		$url = add_query_arg($args ?? [], $baseUrl ?? '');
		return $this->_sanitizeURL($url);
	}

	public function _nonceName($name = null): string {
		return $this->_env('APP_SHORT_NAME', true) . ($name ? '_' . $name : '') . '_nonce';
	}

	public function _slugParams($params = [], $separator = '_') {
		// Lấy toàn bộ query string từ URL
		$request = $this->request ?? $this->getApplication('request');
		$queryParams = $request->query->all();

		$selectedParts = [];

		// Chỉ lấy những params được khai báo
		foreach ($params as $key) {
			if (isset($queryParams[$key])) {
				// Ghép key và value để phân biệt
				$selectedParts[] = $key . '=' . $queryParams[$key];
			}
		}

		// Ghép các phần lại thành một chuỗi
		$slug = implode($separator, $selectedParts);

		// Làm sạch chuỗi thành dạng slug
		$slug = preg_replace('/[^0-9a-zA-Z]/iu', $separator, $slug);

		// Thêm tiền tố app name (nếu có)
		$prefix = $this->_env('APP_SHORT_NAME', true);
		if ($prefix) {
			$slug = $prefix . $separator . $slug;
		}

		// Gán vào biến class
		return $slug;
	}

	public function _isDebug(): bool {
		return $this->_env('APP_DEBUG', true) == 'true';
	}

	public function _isWPDebug(): bool {
		return defined('WP_DEBUG') && WP_DEBUG;
	}

	public function _isWPDebugLog(): bool {
		return defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
	}

	public function _isWPDebugDisplay(): bool {
		return defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY;
	}

	public function _isDev(): bool {
		return $this->_env('APP_ENV', true) == 'dev';
	}

	public function _isLocal(): bool {
		return $this->_env('APP_ENV', true) == 'local';
	}

	public function _isProduction(): bool {
		return $this->_env('APP_ENV', true) == 'production';
	}

	public function _wantsJson(): bool {
		// WordPress AJAX
		if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
			return true;
		}

		// Content-Type (chủ yếu khi client gửi JSON body)
		$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
		if (stripos($contentType, 'application/json') !== false) {
			return true;
		}

		// Client yêu cầu JSON trong Accept Header
		$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
		if (stripos($accept, 'application/json') !== false) {
			return true;
		}

		// AJAX truyền thống từ browser
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			return true;
		}

		return false;
	}

	public function _expectsJson(): bool {
		return $this->_wantsJson();
	}

	public function _escapeRegex($pattern, $delimiter = '/'): string {
		$result = '';
		$depth  = 0;
		$buffer = '';

		for ($i = 0; $i < strlen($pattern); $i++) {
			$char = $pattern[$i];

			if ($char === '(') {
				if ($depth === 0 && $buffer !== '') {
					$result .= preg_quote($buffer, $delimiter);
					$buffer = '';
				}
				$depth++;
				$result .= $char;
			}
			elseif ($char === ')') {
				$depth--;
				$result .= $char;
				if ($depth === 0) {
					// Continue dynamic regex directly
				}
			}
			else {
				if ($depth > 0) {
					$result .= $char;
				}
				else {
					$buffer .= $char;
				}
			}
		}

		if ($buffer !== '') {
			$result .= preg_quote($buffer, $delimiter);
		}

		return $result;
	}

	public function _sanitizeURL($url) {
		$url = trim($url);

		// Nếu chuỗi rỗng => return luôn
		if ($url === '') {
			return '';
		}

		// 🔹 1. Gom các ký tự ? hoặc & liền nhau thành 1 dấu duy nhất (ưu tiên ? đầu tiên)
		$url = preg_replace_callback('/[?&]+/', function($matches) use (&$foundQuestion) {
			if (!isset($foundQuestion)) {
				$foundQuestion = true;
				return '?'; // Giữ lại dấu ? đầu tiên
			}
			return '&'; // Các dấu ? hoặc & tiếp theo đổi thành &
		}, $url);

		// 🔹 2. Xóa & hoặc ? thừa ở đầu/cuối chuỗi
		$url = preg_replace(['#/^(&|\?)#', '/(&|\?)+$/'], '', $url);

		// 🔹 3. Nếu có nhiều ? (trong trường hợp bất thường) -> chỉ giữ cái đầu tiên
		if (substr_count($url, '?') > 1) {
			[$base, $rest] = explode('?', $url, 2);
			$rest = str_replace('?', '&', $rest);
			$url  = $base . '?' . $rest;
		}

		// 🔹 4. Chuẩn hóa query string (parse -> rebuild)
		$parts  = parse_url($url);
		$scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
		$host   = $parts['host'] ?? '';
		$port   = isset($parts['port']) ? ':' . $parts['port'] : '';
		$path   = $parts['path'] ?? '';
		$query  = $parts['query'] ?? '';

		// 🔹 5. Chuẩn hóa lại query string
		if ($query !== '') {
			parse_str($query, $params);
			// Xóa key trùng (nếu cần giữ key cuối)
			$query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
			$url   = $scheme . $host . $port . $path . '?' . $query;
		}
		else {
			$url = $scheme . $host . $port . $path;
		}

		// 🔹 6. Dọn ký tự ? hoặc & cuối cùng (nếu vẫn dư)
		return preg_replace('/(\?|\&)+$/', '', $url);
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

	public function _trailingslash($path) {
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

	public function _normalizeDateTime($value) {
		$tz      = wp_timezone();
		$now     = new \DateTimeImmutable('now', $tz);
		$default = $now;

		if (empty($value)) {
			return $default;
		}

		if ($value instanceof \DateTimeInterface) {
			return $value;
		}

		if (is_numeric($value)) {
			try {
				return (new \DateTimeImmutable('@' . $value))->setTimezone($tz);
			}
			catch (\Throwable $e) {
				return $default;
			}
		}

		// Nếu là chuỗi định dạng ngày hợp lệ
		try {
			$parsed = new \DateTimeImmutable($value, $tz);
			if ($parsed >= $now) {
				return $parsed;
			}
		}
		catch (\Throwable $e) {
			// bỏ qua
		}

		// Nếu là chuỗi kiểu “1 year”, “6 months”, “2 weeks”...
		try {
			$interval = date_interval_create_from_date_string($value);
			if ($interval instanceof \DateInterval) {
				$future = $now->add($interval);
				if ($future >= $now) {
					return $future;
				}
			}
		}
		catch (\Throwable $e) {
			// không parse được
		}

		return $default;
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

	public function _dateDiffForHumans($dateString, $format = 'H:i:s - d/m/Y') {
		try {
			return Carbon::createFromFormat($format, $dateString, wp_timezone_string())->locale(get_locale())->diffForHumans();
		}
		catch (\Throwable $e) {
			return $this->_trans('messages.undefined');
		}
	}

	public function _prefixArrayKeys($array, $prefix = null): array {
		$results = [];
		foreach ($array as $key => $value) {
			$results[$prefix . $key] = $value;
		}
		return $results;
	}

	public function _removePrefixArrayKeys($array, $prefix = null): array {
		$results = [];
		foreach ($array as $key => $value) {
			$key           = preg_replace('/' . $prefix . '/iu', '', $key);
			$results[$key] = $value;
		}
		return $results;
	}

	public function _folderExists($path = null): bool {
		return is_dir($path);
	}

	public function _vendorFolderExists($package = null): bool {
		$vendorPath = $this->_getMainPath('/vendor');
		$package = trim($package, '/');
		return $this->_folderExists($vendorPath . '/' . $package);
	}

}