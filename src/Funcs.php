<?php

namespace WPSPCORE;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use NumberFormatter;
use WPSPCORE\App\Routes\RouteRegexParser;

/**
 * @method static mixed getWPSP()
 * @method static string getWPSPClass()
 * @method static mixed getApplication($abstract = null, array $parameters = [])
 * @method static \WPSPCORE\App\Routes\RouteMap|null getRouteMap()
 * @method static \WPSPCORE\App\Routes\RouteManager|null getRouteManager()
 *
 * @method static string getMainPath($path = null)
 * @method static string getRootNamespace()
 * @method static string getPrefixEnv()
 * @method static string|null getBearerToken($request = null)
 *
 * @method static string getAppShortName()
 * @method static string getMainBaseName()
 * @method static string getSitePath($appendPath = null)
 * @method static string getMainFilePath()
 * @method static string getAppPath($path = null)
 * @method static string getControllerPath($path = null)
 * @method static string getConfigPath($path = null)
 * @method static string getRoutesPath($path = null)
 * @method static string getResourcesPath($path = null)
 * @method static string getStoragePath($path = null)
 * @method static string getDatabasePath($path = null)
 * @method static string getMigrationPath($path = null)
 *
 * @method static string|null getMainUrl()
 * @method static string getPublicUrl($path = null)
 * @method static string getPublicPath($path = null)
 *
 * @method static array getPluginData()
 * @method static string getVersion()
 * @method static string getTextDomain()
 * @method static string getRequiresPhp()
 *
 * @method static array getAllFilesInFolder(string $path)
 * @method static string getDBTablePrefix(bool $withWpdbPrefix = true)
 * @method static string getDBCustomMigrationTablePrefix()
 * @method static string getDBTableName(string $name)
 * @method static string getDBCustomMigrationTableName(string $name)
 *
 * @method static string getPathFromDir(string $targetDir, string $path)
 * @method static array getAllClassesInDir(string $path = __DIR__, string $namespace = __NAMESPACE__)
 *
 * @method static mixed getArrItemByKeyDots(array $array, string $key)
 * @method static mixed getArrItemByKeyValue(array $arr, string $key, $value = null, string $operator = 'equals', bool $single = true)
 *
 * @method static string getPluginDirName()
 * @method static string getPluginDirNameFromPath(string $path)
 * @method static string getPluginDirPathFromPath(string $path)
 * @method static array getWPConfig(string $file = null)
 *
 * @method static mixed app($abstract, array $parameters = [])
 * @method static mixed env(string $var, bool $addPrefix = false, $default = null)
 * @method static \Illuminate\View\View|string|null view($viewName = null, array $data = [], array $mergeData = [], bool $instance = false)
 *
 * @method static void debug($message = '', bool $print = false, bool $varDump = false)
 * @method static \Fruitcake\LaravelDebugbar\LaravelDebugbar|mixed|null debugBar()
 * @method static string|null asset(string $path, $secure = null)
 *
 * @method static string route($routeMap, $routeClass, $routeName, array $args = [], bool $buildURL = false, bool $sanitize = true)
 * @method static string trans(string $string, array $replaces = [], bool $wordpress = false)
 * @method static mixed config($key = null, $default = null)
 * @method static string locale()
 *
 * @method static void notice(string $message = '', string $type = 'info', bool $echo = false, bool $wrap = false, $class = null, bool $dismiss = true)
 * @method static array response(bool $success = false, array $data = [], string $message = '')
 *
 * @method static mixed viewInject($views, $data)
 * @method static mixed viewDetect($viewName = null)
 * @method static \Illuminate\View\Factory|null viewInstance()
 *
 * @method static bool isDebug()
 * @method static bool isDebugBarValid()
 * @method static bool isWPDebug()
 * @method static bool isWPDebugLog()
 * @method static bool isWPDebugDisplay()
 * @method static bool isDev()
 * @method static bool isLocal()
 * @method static bool isProduction()
 * @method static bool wantsJson()
 * @method static bool expectsJson()
 *
 * @method static bool folderExists($path = null)
 * @method static bool vendorFolderExists($package = null)
 * @method static bool hasQueryParams($queryString = null, $targetParams = null, string $relation = 'or')
 * @method static bool isOnlyHasQueryParams($queryString = null, $allowedParams = null)
 * @method static bool isWPInternalRequest($request = null)
 *
 * @method static string buildUrl($baseUrl = null, array $args = [])
 * @method static string nonceName($name = null)
 * @method static string slugParams(array $params = [], string $separator = '_')
 *
 * @method static string regexPath(string $pattern, bool $forceRegex = false, bool $pregQuote = true, string $delimiter = '/')
 * @method static string pregQuoteKeepGroups(string $pattern, string $delimiter = '/')
 *
 * @method static string sanitizeURL(string $url = null)
 * @method static string normalizeURL(string $url = null)
 *
 * @method static array commentTokens()
 * @method static string trailingslash(string $path)
 * @method static string trailingslashit(string $path)
 * @method static string untrailingslashit(string $path)
 *
 * @method static string|null numberFormat($value, int $precision = 0, bool $endWithZeros = true, string $locale = 'vi', string $currencyCode = 'vnd', int $style = \NumberFormatter::DECIMAL, bool $groupingUsed = true)
 * @method static int|float unNumberFormat($value, string $locale = 'vi')
 *
 * @method static \DateTimeInterface normalizeDateTime($value)
 * @method static string dateDiffForHumans($dateString, string $format = 'H:i:s - d/m/Y')
 *
 * @method static array explodeToNestedArray(string $delimiter, string $key, $value)
 * @method static array prefixArrayKeys(array $array, string $prefix = null)
 * @method static array removePrefixArrayKeys(array $array, string $prefix = null)
 */
class Funcs extends BaseInstances {

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

	public function _getWPSP() {
		try {
			$WPSP = $this->WPSPClass::instance();
			return $WPSP;
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	public function _getWPSPClass() {
		return $this->WPSPClass;
	}

	/**
	 * @return \Illuminate\Foundation\Application|\Illuminate\Container\Container|mixed
	 */
	public function _getApplication($abstract = null, $parameters = []) {
		try {
			$app = $this->_getWPSP()->getApplication();

			if ($abstract) {
				return $app->make($abstract, $parameters);
			}

			return $app;
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * @return \WPSPCORE\App\Routes\RouteMap
	 */
	public function _getRouteMap() {
		try {
			$routeMap = $this->routeMapClass::instance();
			return $routeMap;
		}
		catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * @return \WPSPCORE\App\Routes\RouteManager
	 */
	public function _getRouteManager() {
		try {
			$routeManager = $this->routeManagerClass::instance();
			return $routeManager;
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

	public function _getPrefixEnv($suffix = null) {
		return $this->prefixEnv . $suffix;
	}

	/*
	 *
	 */

	public function _getBearerToken($request = null) {
		$request = $request ?? $this->_app('request') ?? null;

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
		if (@preg_match('/Bearer\s+(\S+)/i', trim($authHeader), $matches)) {
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

	public function _getWidenPath($path = null) {
		return $this->_getAppPath() . '/Widen' . ($path ? '/' . ltrim($path, '/\\') : '');
	}

	public function _getMainUrl() {
		try {
			if (!function_exists('plugin_dir_url')) {
				require($this->_getSitePath() . '/wp-admin/includes/plugin.php');
			}
			return rtrim(plugin_dir_url($this->_getMainFilePath()), '/\\');
		}
		catch (\Exception $e) {
			return null;
		}
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
		// 1. Chuẩn hóa tạm thời cả targetDir và path về dạng gạch xuôi '/' để xử lý regex chính xác và không bị lỗi escape kí tự '\'
		$normalizedTargetDir = str_replace('\\', '/', $targetDir);
		$normalizedPath      = str_replace('\\', '/', $path);

		// 2. Thực hiện khớp và thay thế chuỗi bằng regex dựa trên chuỗi đã chuẩn hóa
		$result = preg_replace(
			'/^(.*?)' . preg_quote($normalizedTargetDir, '/') . '(.*?)$/iu',
			$normalizedTargetDir . '$2',
			$normalizedPath
		);

		// 3. CHUẨN HÓA ĐẦU RA: Chuyển đổi toàn bộ dấu gạch chéo về đúng định dạng hệ điều hành hiện tại
		return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $result);
	}

	public function _getAllClassesInDir($path = __DIR__, $namespace = __NAMESPACE__, $depth = null) {
		$finder = new \Symfony\Component\Finder\Finder();
		$finder->files()->in($path)->name('*.php');

		// Tùy chỉnh độ sâu nếu được truyền vào
		if ($depth !== null) {
			$finder->depth($depth);
		}

		$classes = [];

		foreach ($finder as $file) {
			try {
				$relativePath = $file->getRelativePath(); // vd: "SubDir/ChildDir" hoặc ""

				// Chuyển đổi đường dẫn thư mục thành Namespace (hỗ trợ cả Windows/Linux)
				$subNamespace = $relativePath ? str_replace('/', '\\', str_replace('\\', '/', $relativePath)) : '';

				// Build namespace đầy đủ một cách gọn gàng, loại bỏ các dấu \ thừa
				$className = rtrim($namespace, '\\');
				if ($subNamespace) {
					$className .= '\\' . $subNamespace;
				}
				$className .= '\\' . $file->getFilenameWithoutExtension();

				if (class_exists($className) && $className !== __CLASS__) {
					$classes[] = $className;
				}
			}
			catch (\Throwable $e) {
				continue;
			}
		}

		return $classes;
	}

	public function _getAllDirsInDir($path, $depth = null): array {
		// 1. Kiểm tra nếu đường dẫn không tồn tại hoặc không phải thư mục
		if (!is_dir($path)) {
			return [];
		}

		$finder = new \Symfony\Component\Finder\Finder();

		// 2. Chỉ cấu hình tìm kiếm THƯ MỤC (directories) thay vì file
		$finder->directories()->in($path);

		// 3. Tùy chỉnh độ sâu nếu được truyền vào
		if ($depth !== null) {
			$finder->depth($depth);
		}

		$directories = [];

		// 4. Lặp qua các thư mục tìm được
		foreach ($finder as $dir) {
			try {
				$directories[] = [
					'name'          => $dir->getFilename(),
					'absolute_path' => $dir->getRealPath(),
					'relative_path' => $dir->getRelativePathname()
				];
			} catch (\Throwable $e) {
				continue;
			}
		}

		return $directories;
	}

	public function _getAllFilesInDir($path, $depth = null): array {
		// 1. Kiểm tra nếu đường dẫn cha không hợp lệ
		if (!is_dir($path)) {
			return [];
		}

		$finder = new \Symfony\Component\Finder\Finder();

		// 2. Cấu hình tìm kiếm FILE
		$finder->files()->in($path);

		// 3. Tùy chỉnh độ sâu
		if ($depth !== null) {
			$finder->depth($depth);
		}

		$files = [];

		// 4. Lặp qua các file và thu thập tối đa thông tin
		foreach ($finder as $file) {
			try {
				$absolutePath = $file->getRealPath();

				// Lấy quyền truy cập dạng Octal (Ví dụ: "0644")
				$perms = $file->getPerms();
				$formattedPerms = substr(sprintf('%o', $perms), -4);

				$files[] = [
					// Thông tin định danh & Đường dẫn
					'name'               => $file->getFilename(),                 // Tên file kèm đuôi (vd: "index.php")
					'filename_no_ext'    => $file->getFilenameWithoutExtension(), // Tên file không kèm đuôi (vd: "index")
					'extension'          => $file->getExtension(),                 // Đuôi file (vd: "php")
					'absolute_path'      => $absolutePath,                         // Đường dẫn tuyệt đối
					'relative_path'      => $file->getRelativePath(),              // Thư mục cha tương đối (vd: "SubDir")
					'relative_pathname'  => $file->getRelativePathname(),          // Đường dẫn tương đối đầy đủ (vd: "SubDir/index.php")

					// Thuộc tính vật lý
					'size_bytes'         => $file->getSize(),                      // Dung lượng (Bytes)
					'size_readable'      => $this->_formatBytes($file->getSize()), // Dung lượng dễ đọc (vd: "1.2 MB")
					'mime_type'          => mime_content_type($absolutePath) ?: 'unknown', // Loại file (vd: "text/x-php", "image/jpeg")
					'is_readable'        => $file->isReadable(),
					'is_writable'        => $file->isWritable(),
					'permissions'        => $formattedPerms,                       // Quyền hạn file (vd: "0644")

					// Mốc thời gian (Timestamp)
					'created_time'       => $file->getCTime(),                     // Thay đổi inode/Tạo (tùy OS)
					'modified_time'      => $file->getMTime(),                     // Thay đổi nội dung gần nhất
					'accessed_time'      => $file->getATime(),                     // Truy cập gần nhất

					// Bảo mật / Kiểm tra toàn vẹn
					'md5_hash'           => md5_file($absolutePath),               // Mã hash kiểm tra trùng lặp
					'owner_id'           => $file->getOwner(),                     // ID User sở hữu trong Linux
					'group_id'           => $file->getGroup(),                     // ID Group sở hữu trong Linux
				];
			} catch (\Throwable $e) {
				// Bỏ qua nếu file bị lỗi quyền truy cập hoặc bị xóa đột ngột trong lúc quét
				continue;
			}
		}

		return $files;
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
						if (isset($item[$key]) && @preg_match('/' . $value . '/iu', $item[$key])) {
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

	public function _getPluginDirNameFromPath($path): string {
		// 1. Chuẩn hóa tất cả đường dẫn về dấu gạch xuôi '/'
		$normalizedPath = str_replace('\\', '/', $path);

		// 2. Lấy đường dẫn thư mục plugins chuẩn của WordPress và chuẩn hóa nó
		$pluginDir = defined('WP_PLUGIN_DIR') ? str_replace('\\', '/', WP_PLUGIN_DIR) : 'wp-content/plugins';

		// 3. Nếu đường dẫn file thực sự nằm trong thư mục plugins của hệ thống
		if (str_starts_with($normalizedPath, $pluginDir)) {
			// Cắt bỏ phần gốc: Chỉ giữ lại phần nằm sau "wp-content/plugins/"
			$relativeToPlugins = ltrim(substr($normalizedPath, strlen($pluginDir)), '/');

			// Trích xuất thư mục đầu tiên (tên plugin)
			$parts = explode('/', $relativeToPlugins);
			return !empty($parts[0]) ? $parts[0] : 'unknown';
		}

		// 4. Phương án dự phòng (Fallback) dùng Regex chuẩn hóa nếu hằng số WP_PLUGIN_DIR chưa được định nghĩa
		if (preg_match('/wp-content\/plugins\/([^\/]+)/', $normalizedPath, $matches)) {
			return $matches[1];
		}

		return 'unknown';
	}

	public function _getPluginDirPathFromPath($path): string {
		// 1. Chuẩn hóa tất cả đường dẫn về dấu gạch xuôi '/' để xử lý chuỗi ổn định (không phân biệt OS)
		$normalizedPath = str_replace('\\', '/', $path);

		// 2. Lấy đường dẫn thư mục plugins chuẩn của WordPress và chuẩn hóa nó về dạng '/'
		$pluginDir = defined('WP_PLUGIN_DIR') ? str_replace('\\', '/', WP_PLUGIN_DIR) : '';

		// Nếu WP_PLUGIN_DIR chưa được định nghĩa (chạy CLI/Console ngoài WP), tìm vị trí wp-content/plugins trong chuỗi
		if (empty($pluginDir)) {
			$pos = strpos($normalizedPath, 'wp-content/plugins');
			if ($pos !== false) {
				$pluginDir = substr($normalizedPath, 0, $pos + 18); // 18 là độ dài của 'wp-content/plugins'
			}
		} else {
			$pluginDir = str_replace('\\', '/', $pluginDir);
		}

		$pluginDir = rtrim($pluginDir, '/');
		$resultPath = 'unknown';

		// 3. Nếu xác định được thư mục plugins gốc
		if (!empty($pluginDir) && str_starts_with($normalizedPath, $pluginDir)) {
			// Cắt bỏ phần gốc để lấy phần tương đối sau "plugins/"
			$relativeToPlugins = ltrim(substr($normalizedPath, strlen($pluginDir)), '/');

			// Trích xuất tên thư mục plugin đầu tiên
			$parts = explode('/', $relativeToPlugins);
			if (!empty($parts[0])) {
				$resultPath = $pluginDir . '/' . $parts[0];
			}
		}

		// 4. Phương án dự phòng (Fallback) sử dụng Regex nếu các cách trên không khớp
		if ($resultPath === 'unknown' && preg_match('/^(.*\/wp-content\/plugins\/([^\/]+))/', $normalizedPath, $matches)) {
			$resultPath = $matches[1]; // Trả về toàn bộ đường dẫn tính đến hết tên thư mục plugin
		}

		// 5. CHUẨN HÓA ĐẦU RA: Chuyển đổi toàn bộ dấu gạch chéo theo đúng định dạng hệ điều hành hiện tại (Windows: \, Linux: /)
		if ($resultPath !== 'unknown') {
			return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $resultPath);
		}

		return 'unknown';
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

	public function _app($abstract = null, $parameters = []) {
		if (!$abstract) {
			return $this->_getApplication();
		}
		else {
			return $this->_getApplication($abstract, $parameters);
		}
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

	public function _auth($guard = null) {

		/** @var \Illuminate\Support\Facades\Auth $auth */
		$auth = $this->_app('auth');

		if ($guard && $guard !== 'web') {
			$auth->shouldUse($guard);
		}

		return $auth;
	}

	public function _view($viewName = null, $data = [], $mergeData = [], $instance = false) {
		/** @var \Illuminate\View\Factory $blade */
		$blade = $this->_app('view');

		try {
			/** @var \Fruitcake\LaravelDebugbar\LaravelDebugbar $debugbar */
			if ($this->_isDebugBarValid()) {
				$debugbar = $this->_app('debugbar');
			}

			if (!$viewName && $instance) {
				return $blade ?? null;
			}

			if ($blade !== null) {
				if (isset($debugbar) && $debugbar->isEnabled() && $debugbar->shouldCollect('views')) {
					$debugbar['time']?->startMeasure('views', 'Views');
				}

				$content = $blade->make($viewName, $data, $mergeData);

				if (isset($debugbar) && $debugbar->isEnabled() && $debugbar->shouldCollect('views')) {
					$debugbar['time']?->stopMeasure('views');
				}

				return $content;
			}
			return null;
		}
		catch (\Throwable $e) {
			return '<div class="wrap"><div class="notice notice-error"><p>' . $e->getMessage() . '</p></div></div>';
		}
	}

	public function _viewInject($views, $data) {
		if ($data instanceof \Closure) {
			return $this->_viewInstance()?->composer($views, $data);
		}
		elseif (is_array($data)) {
			return $this->_viewInstance()?->composer($views, function(View $view) use ($data) {
				foreach ($data as $key => $value) {
					$view->with($key, $value);
				}
			});
		}
		else {
			return false;
		}
	}

	public function _viewDetect($viewName = null) {
		return $viewName;
	}

	public function _viewInstance() {
		return $this->_view(null, [], [], true);
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

	/**
	 * @return \Fruitcake\LaravelDebugbar\LaravelDebugbar|mixed|null
	 */
	public function _debugBar() {
		if ($this->_isDebugBarValid()) {
			return $this->_app('debugbar');
		}
		else {
			return null;
		}
	}

	public function _asset($path, $secure = null) {
		try {
			if (!function_exists('plugin_dir_url')) {
				return null;
			}
			return $this->_getPublicUrl() . '/' . ltrim($path, '/\\');
		}
		catch (\Exception $e) {
			return null;
		}
	}

	public function _route($routeClass, $routeName, $args = [], $buildURL = false, $sanitize = true, $routeMap = null) {
		if (!$routeMap) return '';

		// Normalize
		if (@preg_match('/\\\\/', $routeClass)) {
			$parts = explode('\\', trim($routeClass, '\\'));
			$routeClass = end($parts);
		}

		$map = $routeMap[$routeClass][$routeName] ?? null;
		if (!$map) return '';

		switch ($routeClass) {
			case 'Apis':
				$routeUrl = $map['namespace'] . '\/' . $map['version'] . '\/' . $map['full_path_regex'];
				break;
			default:
				$routeUrl = $map['full_path_regex'];
		}

		// ❗ Plain version (dùng cho xây URL)
		$finalUrl = $routeUrl;

		// Xử lý param dạng param={key} và param={key?}
		if (@preg_match_all('/(\w+)=\{(\w+)(\?)?}/', $finalUrl, $m)) {
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
		if (@preg_match_all('/\{(\w+)(\?)?}/', $finalUrl, $pm)) {
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

		$parser   = new RouteRegexParser($routeUrl, $sanitize);
		$finalUrl = $parser->build($args);

		// Xử lý non-capture group dạng (?: ... (?P<name>regex) ...)?
//		if (@preg_match_all('/\(\?:([^()]*?\(\?P<([^>]+)>[^)]+\)[^()]*?)\)\?/', $finalUrl, $nm)) {
//			foreach ($nm[2] as $i => $name) {
//				$fullGroup = $nm[0][$i]; // toàn bộ (?: ... )?
//				$inner     = $nm[1][$i]; // phần bên trong
//
//				if (is_array($args) && array_key_exists($name, $args)) {
//					// Extract the regex inside (?P<name>regex)
//					if (@preg_match('/\??\(\?P<' . $name . '>([^)]+)\)\??/', $inner, $im)) {
//						$value = rawurlencode($args[$name]);
//						// replace non capture block with actual inserted value
//						$replacement = str_replace($im[0], $value, $inner);
//						$replacement = ltrim($replacement, '/\\');
//						$finalUrl = str_replace($fullGroup, '/' . $replacement, $finalUrl);
//					}
//					unset($args[$name]);
//				} else {
//					// Không có tham số → xóa toàn bộ block
//					$finalUrl = str_replace($fullGroup, '', $finalUrl);
//				}
//			}
//		}

		// Xử lý group PATH dạng (?P<key>regex) và (?P<key>regex)?
//		if (@preg_match_all('/\??\(\?P<([^>]+)>([^)]+)\)\??/', $finalUrl, $gm)) {
//			foreach ($gm[1] as $i => $name) {
//				$fullGroup = $gm[0][$i];
//
//				if (is_array($args) && array_key_exists($name, $args)) {
//					$value = rawurlencode($args[$name]);
//				} else {
//					$value = ''; // Không có value → rỗng
//				}
//
//				// Thay group bằng value
//				$finalUrl = str_replace($fullGroup, $value, $finalUrl);
//
//				unset($args[$name]); // Đã dùng, xoá tránh append query
//			}
//		}

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

		// Cleanup.
		$finalUrl = trim($finalUrl, '?$&');

		// Normalize URL.
		if ($sanitize) {
			$finalUrl = $this->_normalizeURL($finalUrl);
		}

		return $finalUrl;
	}

	public function _trans($string, $replaces = [], $wordpress = false) {
		try {
			if ($wordpress || !class_exists('Illuminate\Translation\Translator')) {
				return __($string, $this->_getTextDomain());
			}
			else {
				/** @var \Illuminate\Translation\Translator $translation */
				$translation = $this->_app('translator');
				return $translation->has($string) ? $translation->get($string, $replaces) : $translation->get($string, $replaces, $this->_config('app.fallback_locale'));
			}
		}
		catch (\Throwable $e) {
			return $string;
		}
	}

	public function _config($key = null, $default = null) {
		try {
			$config = $this->_app('config');
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

	/*
	 *
	 */


	/*
	 * Boolean methods.
	 */

	public function _isDebug() {
		return $this->_env('APP_DEBUG', true) == 'true';
	}

	public function _isDebugBarValid() {
		if (
			!$this->_app()->runningInConsole()
			&& $this->_env($this->_getPrefixEnv('APP_DEBUG_MONITOR')) === true
			&& class_exists('\Fruitcake\LaravelDebugbar\LaravelDebugbar')
			&& !wp_doing_ajax()
			&& !wp_doing_cron()
			&& !wp_is_serving_rest_request()
			&& !defined('REST_REQUEST')
		) {
			return true;
		}
		else {
			return false;
		}
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

	public function _isOnlyHasQueryParams($queryString = null, $allowedParams = null) {
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

	public function _isWPInternalRequest(?Request $request = null): bool {
		if (
			(defined('DOING_CRON') && DOING_CRON)
			|| (defined('WP_CLI') && WP_CLI)
			|| php_sapi_name() === 'cli'
		) {
			return true;
		}

		$userAgent = $request ? $request->userAgent() : ($this->request?->userAgent() ?? null);

		if ($userAgent && @preg_match('#^WordPress/#i', $userAgent)) {
			return true;
		}

		return false;
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
		$request = $this->request ?? $this->_app('request');
		$queryParams = $request->query->all();

		$selectedParts = [];

		// Chỉ lấy những params được khai báo
		foreach ($params as $key) {
			// Ghép key và value để phân biệt
			$selectedParts[] = $key . '=' . ($queryParams[$key] ?? null);
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

	public function _regexPath($pattern, $forceRegex = false, $pregQuote = true, $delimiter = '/') {
		// Nếu chứa ký tự escaped slash -> đang là regex thật -> trả về nguyên
		if (str_contains($pattern, '\\') || $forceRegex) {
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

	public function _formatBytes(int $bytes, int $precision = 2): string {
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
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

	public function _sanitizeURL($url = null) {
		if (!$url) return $url;

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

	public function _normalizeURL($url = null) {
		return $this->_sanitizeURL($url);
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

	public function _unNumberFormat($value, $locale = 'vi') {
		if ($value === null || $value === '') {
			return 0;
		}

		// Convert to string if not already
		$value = (string) $value;

		// Remove all whitespace (including non-breaking spaces used in some locales)
		$value = preg_replace('/\s+/u', '', $value);

		// Determine separators based on locale
		$formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
		$decimalSep  = $formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
		$groupingSep = $formatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);

		// Remove thousands separator, then normalize decimal separator to '.'
		$value = str_replace($groupingSep, '', $value);
		$value = str_replace($decimalSep, '.', $value);

		// Strip any remaining non-numeric characters (except minus and decimal point)
		$value = preg_replace('/[^0-9.\-]/', '', $value);

		// Cast to appropriate type
		if (str_contains($value, '.')) {
			return (float) $value;
		}

		return (int) $value;
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

	/*
	 *
	 */

	public static function __callStatic($method, $parameters) {
		$method = '_' . $method;

		if (!method_exists(static::instance(), $method)) {
			throw new \BadMethodCallException(
				sprintf(
					'Call to undefined method %s::%s',
					static::class,
					$method
				)
			);
		}

		return static::instance()->$method(...$parameters);
	}

}