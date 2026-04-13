<?php

namespace WPSPCORE;

use Carbon\Carbon;
use Illuminate\View\View;
use NumberFormatter;
use WPSPCORE\App\Routes\RouteTrait;

class Funcs extends BaseInstances {

	use RouteTrait;

	public $WPSPClass;
	public $routeMapClass;
	public $routeManagerClass;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->WPSPClass = '\\' . $this->rootNamespace . '\WPSP';
		$this->routeMapClass = '\\' . $this->rootNamespace . '\App\Widen\Routes\RouteMap';
		$this->routeManagerClass = '\\' . $this->rootNamespace . '\App\Widen\Routes\RouteManager';
	}

	/*
	 *
	 */

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

	/**
	 * @return \WPSPCORE\App\Routes\RouteMap
	 */
	public function getRouteMap(): ?app\Routes\RouteMap {
		try {
			return $this->routeMapClass::instance();
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * @return \WPSPCORE\App\Routes\RouteManager
	 */
	public function getRouteManager(): ?app\Routes\RouteManager {
		try {
			return $this->routeManagerClass::instance();
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	/*
	 *
	 */

	public function _getMainPath($path = null) {
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

	public function _getBearerToken($request = null) {
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

	public function _getMainBaseName() {
		return basename($this->_getMainPath());
	}

	public function _getSitePath($appendPath = null) {
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

	public function _getMainFilePath() {
		return $this->_getMainPath() . '/main.php';
	}

	public function _getAppPath($path = null) {
		return $this->_getMainPath() . '/app' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getControllerPath($path = null) {
		return $this->_getAppPath() . '/Http/Controllers' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getConfigPath($path = null) {
		return $this->_getMainPath() . '/config' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getRoutesPath($path = null) {
		return $this->_getMainPath() . '/routes' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getResourcesPath($path = null) {
		return $this->_getMainPath() . '/resources' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getStoragePath($path = null) {
		return $this->_getMainPath() . '/storage' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getDatabasePath($path = null) {
		return $this->_getMainPath() . '/database' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getMigrationPath($path = null) {
		return $this->_getDatabasePath() . '/migrations' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getMainUrl() {
		if (!function_exists('plugin_dir_url')) {
			require($this->_getSitePath() . '/wp-admin/includes/plugin.php');
		}
		return rtrim(plugin_dir_url($this->_getMainFilePath()), '/\\');
	}

	public function _getPublicUrl($path = null) {
		return $this->_getMainUrl() . '/public' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getPublicPath($path = null) {
		return $this->_getMainPath() . '/public' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getPluginData() {
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

	public function _getAllFilesInFolder($path) {
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

	public function _getDBCustomMigrationTablePrefix() {
		return $this->_getDBTablePrefix() . 'cm_';
	}

	public function _getDBTableName($name) {
		return $this->_getDBTablePrefix() . $name;
	}

	public function _getDBCustomMigrationTableName($name) {
		return $this->_getDBTablePrefix() . 'cm_' . $name;
	}

	public function _getPathFromDir($targetDir, $path) {
		return preg_replace('/^(.*?)' . $targetDir . '(.*?)$/iu', $targetDir . '$2', $path);
	}

	public function _getAllClassesInDir($namespace = __NAMESPACE__, $path = __DIR__) {
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

	public function _getPluginDirName() {
		return $this->_getMainBaseName();
	}

	public function _getWPConfig($file = null) {
		if (!$file) {
			$file = $this->_getSitePath() . '/wp-config.php';
		}

		if (!file_exists($file)) {
			return [];
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

	public function _asset($path, $secure = null) {
		return $this->_getPublicUrl() . '/' . ltrim($path, '/\\');
	}

	public function _route($routeMap, $routeClass, $routeName, $args = [], $buildURL = false) {
		// Normalize
		if (preg_match('/\\\\/', $routeClass)) {
			$parts = explode('\\', trim($routeClass, '\\'));
			$routeClass = end($parts);
		}

		$map = $routeMap[$routeClass][$routeName] ?? null;
		if (!$map) return '';

		switch ($routeClass) {
			case 'Apis':
				$routeUrl = $map['namespace'] . '/' . $map['version'] . '/' . $map['full_path'];
				break;
			default:
				$routeUrl = $map['full_path'];
		}

		// ❗ Plain version (dùng cho xây URL)
		$finalUrl = $routeUrl;

		// Xử lý param dạng param={key} và param={key?}
		if (preg_match_all('/(\w+)=\{(\w+)(\?)?}/', $finalUrl, $m)) {
			foreach ($m[1] as $i => $paramKey) {
				$paramName = $m[2][$i];
				$fullTag   = $m[0][$i];

				if (is_array($args) && array_key_exists($paramName, $args)) {
					// Có value
					$value = rawurlencode($args[$paramName]);
					$finalUrl = str_replace($fullTag, $paramKey . '=' . $value, $finalUrl);
					unset($args[$paramName]);
				} else {
					// Không có value → key=
					$finalUrl = str_replace($fullTag, $paramKey . '=', $finalUrl);
				}
			}
		}

		// Xử lý placeholder dạng {key} và {key?}
		if (preg_match_all('/\{(\w+)(\?)?}/', $finalUrl, $pm)) {
			foreach ($pm[1] as $i => $name) {
				$fullTag = $pm[0][$i];

				if (is_array($args) && array_key_exists($name, $args)) {
					// Thay bằng giá trị thực
					$value = rawurlencode($args[$name]);
					$finalUrl = str_replace($fullTag, $value, $finalUrl);
					unset($args[$name]);
				} else {
					// Không có value → bỏ luôn placeholder
					$finalUrl = str_replace($fullTag, '', $finalUrl);
				}
			}
		}

		// Xử lý non-capture group dạng (?: ... (?P<name>regex) ...)?
		if (preg_match_all('/\(\?:([^()]*?\(\?P<([^>]+)>[^)]+\)[^()]*?)\)\?/', $finalUrl, $nm)) {
			foreach ($nm[2] as $i => $name) {
				$fullGroup = $nm[0][$i]; // toàn bộ (?: ... )?
				$inner     = $nm[1][$i]; // phần bên trong

				if (is_array($args) && array_key_exists($name, $args)) {
					// Extract the regex inside (?P<name>regex)
					if (preg_match('/\??\(\?P<' . $name . '>([^)]+)\)\??/', $inner, $im)) {
						$value = rawurlencode($args[$name]);
						// replace non capture block with actual inserted value
						$replacement = str_replace($im[0], $value, $inner);
						$replacement = ltrim($replacement, '/\\');
						$finalUrl = str_replace($fullGroup, '/' . $replacement, $finalUrl);
					}
					unset($args[$name]);
				} else {
					// Không có tham số → xóa toàn bộ block
					$finalUrl = str_replace($fullGroup, '', $finalUrl);
				}
			}
		}

		// Xử lý group PATH dạng (?P<key>regex) và (?P<key>regex)?
		if (preg_match_all('/\??\(\?P<([^>]+)>([^)]+)\)\??/', $finalUrl, $gm)) {
			foreach ($gm[1] as $i => $name) {
				$fullGroup = $gm[0][$i];

				if (is_array($args) && array_key_exists($name, $args)) {
					$value = rawurlencode($args[$name]);
				} else {
					$value = ''; // Không có value → rỗng
				}

				// Thay group bằng value
				$finalUrl = str_replace($fullGroup, $value, $finalUrl);

				unset($args[$name]); // Đã dùng, xoá tránh append query
			}
		}

		// Xóa tag nhóm regex nếu còn sót.
//		$finalUrl = preg_replace('/\((.*?)\)/', '', $finalUrl);

		// Nếu còn args → append query string
		if (!empty($args) && is_array($args)) {
			$finalUrl = add_query_arg($args, $finalUrl);
			$finalUrl = rawurldecode($finalUrl);
		}

		// Cleanup
		$finalUrl = trim($finalUrl, '?$&');

		// Build thành URL đầy đủ
		if ($buildURL || (is_bool($args) && $args)) {
			switch ($routeClass) {
				case 'Apis':
					$finalUrl = rest_url($finalUrl);
					break;
				case 'Ajaxs':
					$finalUrl = admin_url('admin-ajax.php?action=' . $finalUrl);
					break;
				case 'AdminPages':
					$finalUrl = admin_url('admin.php?page=' . $finalUrl);
					break;
				case 'FrontPages':
				case 'RewriteFrontPages':
					$finalUrl = home_url($finalUrl);
					break;
			}
		}

		$finalUrl = trim($finalUrl, '/\\');
		$finalUrl = preg_replace('/\\\\\//', '/', $finalUrl);

		// Remove double slash (//) nhưng giữ prefix như https://
		$finalUrl = preg_replace('#(?<!:)//+#', '/', $finalUrl);

		// Cleanup
		$finalUrl = trim($finalUrl, '?$&');

		return $finalUrl;
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

	public function _response($success = false, $data = [], $message = '') {
		return [
			'success' => $success,
			'data'    => $data,
			'message' => $message,
		];
	}

	public function _viewInject($views, $data) {
		if ($data instanceof \Closure) {
			return $this->_viewInstance()->composer($views, $data);
		}
		elseif (is_array($data)) {
			return $this->_viewInstance()->composer($views, function(View $view) use ($data) {
				foreach ($data as $key => $value) {
					$view->with($key, $value);
				}
			});
		}
		else {
			return false;
		}
	}

	public function _viewInstance() {
		return $this->_view(null, [], [], true);
	}

	/*
	 *
	 */


	/*
	 * Boolean methods.
	 */

	public function _isDebug() {
		return $this->_env('APP_DEBUG', true) == 'true';
	}

	public function _isWPDebug() {
		return defined('WP_DEBUG') && WP_DEBUG;
	}

	public function _isWPDebugLog() {
		return defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
	}

	public function _isWPDebugDisplay() {
		return defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY;
	}

	public function _isDev() {
		return $this->_env('APP_ENV', true) == 'dev';
	}

	public function _isLocal() {
		return $this->_env('APP_ENV', true) == 'local';
	}

	public function _isProduction() {
		return $this->_env('APP_ENV', true) == 'production';
	}

	public function _wantsJson() {
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

	public function _expectsJson() {
		return $this->_wantsJson();
	}

	public function _folderExists($path = null) {
		return is_dir($path);
	}

	public function _vendorFolderExists($package = null) {
		$vendorPath = $this->_getMainPath('/vendor');
		$package = trim($package, '/\\');
		return $this->_folderExists($vendorPath . '/' . $package);
	}

	public function _hasQueryParams($queryString = null, $targetParams = null, $relation = 'or') {
		if (!$queryString || !$targetParams) {
			return false;
		}

		parse_str($queryString, $query);

		$relation    = strtolower($relation);
		$ruleResults = [];

		// Chuẩn hóa string → rule đơn
		if (is_string($targetParams)) {
			$targetParams = [$targetParams];
		}

		// Duyệt từng RULE
		foreach ($targetParams as $ruleKey => $rule) {

			$ruleRelation = 'or';
			$params       = [];

			/**
			 * RULE dạng:
			 * [
			 *   'relation' => 'and|or',
			 *   'params' => [...]
			 * ]
			 */
			if (is_array($rule) && isset($rule['params'])) {
				$ruleRelation = strtolower($rule['relation'] ?? 'or');
				$params       = $rule['params'];
			}

			/**
			 * RULE dạng đơn:
			 * 'action'
			 * ['action', 'abc']
			 * ['action' => 'show']
			 * 'one' => 'two'
			 */
			else {
				if (is_int($ruleKey)) {
					$params = is_array($rule) ? $rule : [$rule];
				}
				else {
					$params = [$ruleKey => $rule];
				}
			}

			// ===== MATCH PARAMS TRONG RULE =====
			$matches = [];

			foreach ($params as $key => $expectedValue) {

				// ['action', 'abc']
				if (is_int($key)) {
					$matches[] = array_key_exists($expectedValue, $query);
					continue;
				}

				// ['action' => 'show']
				if (!array_key_exists($key, $query)) {
					$matches[] = false;
					continue;
				}

				// chỉ check key
				if ($expectedValue === null) {
					$matches[] = true;
					continue;
				}

				// check value
				$matches[] = (string)$query[$key] === (string)$expectedValue;
			}

			// Kết quả của RULE
			$ruleResults[] = ($ruleRelation === 'and')
				? !in_array(false, $matches, true)
				: in_array(true, $matches, true);
		}

		// ===== KẾT HỢP CÁC RULE =====
		if ($relation === 'and') {
			return !in_array(false, $ruleResults, true);
		}

		// OR (default)
		return in_array(true, $ruleResults, true);
	}

	public function _onlyHasQueryParams($queryString = null, $allowedParams = null) {
		if (!$queryString || !$allowedParams) {
			return false;
		}

		parse_str($queryString, $query);

		// Chuẩn hóa string
		if (is_string($allowedParams)) {
			$allowedParams = [trim($allowedParams)];
		}

		$allowedKeys = [];
		$valueRules  = [];

		/**
		 * Chuẩn hóa allowedParams thành:
		 * - allowedKeys: danh sách key được phép
		 * - valueRules:  key => [value1, value2...]
		 */
		foreach ($allowedParams as $k => $rule) {

			// Case: ['action', 'abc']
			if (is_int($k) && is_string($rule)) {
				$allowedKeys[] = $rule;
				continue;
			}

			// Case: ['action' => 'show']
			if (is_string($k)) {
				$allowedKeys[]    = $k;
				$valueRules[$k][] = (string)$rule;
				continue;
			}

			// Case: [['action'=>'show'], ['abc'=>'xyz']]
			if (is_array($rule)) {
				foreach ($rule as $rk => $rv) {
					$allowedKeys[]     = $rk;
					$valueRules[$rk][] = (string)$rv;
				}
			}
		}

		$allowedKeys = array_unique($allowedKeys);

		// ===== 1. Check key whitelist =====
		$invalidKeys = array_diff(array_keys($query), $allowedKeys);
		if (!empty($invalidKeys)) {
			return false;
		}

		// ===== 2. Check value rules =====
		foreach ($valueRules as $key => $allowedValues) {
			if (array_key_exists($key, $query)) {
				if (!in_array((string)$query[$key], $allowedValues, true)) {
					return false;
				}
			}
		}

		return true;
	}

	/*
	 *
	 */

	public function _buildUrl($baseUrl = null, $args = []) {
		$url = add_query_arg($args ?? [], $baseUrl ?? '');
		return $this->_sanitizeURL($url);
	}

	public function _nonceName($name = null) {
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

	public function _regexPath($pattern, $pregQuote = true, $delimiter = '/') {
		// Nếu chứa ký tự escaped slash -> đang là regex thật -> trả về nguyên
		if (strpos($pattern, '\/') !== false) {
			$pattern = preg_replace('/(?<!\\\\)(?:\\\\\\\\)*\//', '\\/', $pattern);
			$pattern = preg_replace('/(?:\\\\\/){2,}/', '\\/', $pattern);
			return $pattern;
		}

		// Query params dạng: param={id?}
		$pattern = preg_replace_callback('/(\w+)=\{(\w+)\?}/', function($m) {
			return $m[1] . '(?:=(?P<' . $m[2] . '>[^&]+))?';
		}, $pattern);

		// Query params dạng: param={id}
		$pattern = preg_replace_callback('/(\w+)=\{(\w+)}/', function($m) {
			return $m[1] . '=(?P<' . $m[2] . '>[^&]+)';
		}, $pattern);

		// Query params dạng: {id?}
		$pattern = preg_replace_callback('/\{(\w+)\?}/', function($m) {
			return '(?P<' . $m[1] . '>[^\/]+)?';
		}, $pattern);

		// Query params dạng: {id}
		$pattern = preg_replace_callback('/\{(\w+)}/', function($m) {
			return '(?P<' . $m[1] . '>[^\/]+)';
		}, $pattern);

		// Query params dạng: key=(?P<id>xxx)?
		$pattern = preg_replace('/(\w+)=\((\?P<[^>]+>[^)]+)\)\?/', '$1(?:=($2))?', $pattern);

		// Query params dạng: key=(?P<id>...)
		$pattern = preg_replace('/(\w+)=\((\?P<[^>]+>[^)]+)\)/', '$1=($2)', $pattern);

		// Không có regex, không param -> escape path thuần
		$pattern = $pregQuote ? $this->_pregQuoteKeepGroups($pattern, $delimiter) : $pattern;

		return $pattern;
	}

	public function _pregQuoteKeepGroups($pattern, $delimiter = '/') {
		// 1. Tách toàn bộ group
		$groups = [];
		$placeholder = '___REGEX_GROUP_%d___';
		$i = 0;

		// Match đúng mọi group kể cả lồng nhau
		$patternWithPlaceholders = preg_replace_callback(
			'/\((?:[^()\\\\]|\\\\.|(?R))*\)\??/',
			function($m) use (&$groups, $placeholder, &$i) {
				$groups[$i] = $m[0];
				return sprintf($placeholder, $i++);
			},
			$pattern
		);

		// 2. Escape toàn bộ pattern
		$quoted = preg_quote($patternWithPlaceholders, $delimiter);

		// 3. Khôi phục dấu "=" trước group
		$quoted = preg_replace(
			'/\\\\=(___REGEX_GROUP_\d+___)/',
			'=\1',
			$quoted
		);

		// 4. Trả lại group
		foreach ($groups as $idx => $group) {
			$quoted = str_replace(sprintf($placeholder, $idx), $group, $quoted);
		}

		return $quoted;
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

	public function _commentTokens() {
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

	public function _trailingslashit($path) {
		$path = str_replace('\\', '/', $path);
		$path = rtrim($path, '/\\');
		return $path . '/';
	}

	public function _untrailingslashit($path) {
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

	public function _dateDiffForHumans($dateString, $format = 'H:i:s - d/m/Y') {
		try {
			return Carbon::createFromFormat($format, $dateString, wp_timezone_string())->locale(get_locale())->diffForHumans();
		}
		catch (\Throwable $e) {
			return $this->_trans('messages.undefined');
		}
	}

	public function _explodeToNestedArray($delimiter, $key, $value) {
		$keys = explode($delimiter, $key);
		while ($key = array_pop($keys)) {
			$value = [$key => $value];
		}
		return $value;
	}

	public function _prefixArrayKeys($array, $prefix = null) {
		$results = [];
		foreach ($array as $key => $value) {
			$results[$prefix . $key] = $value;
		}
		return $results;
	}

	public function _removePrefixArrayKeys($array, $prefix = null) {
		$results = [];
		foreach ($array as $key => $value) {
			$key           = preg_replace('/' . $prefix . '/iu', '', $key);
			$results[$key] = $value;
		}
		return $results;
	}

}