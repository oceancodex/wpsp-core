<?php
/**
 * Created by PhpStorm.
 * User: Khanh
 * Date: 05/10/2026
 * Time: 8:56 CH
 */

namespace WPSPCORE\App\App;

use WPSPCORE\App\Console\Command;

class Application extends Container {

	public $name;
	public $funcs;
	public $version;
	public $basePath;
	public $commands = [];
	public $useColor;

	public $except = [
		'KeyGenerateCommand',
		'ModelMakeCommand',
		'SeedCommand',
		'SeederMakeCommand',
		'WipeCommand',
	];

	public function __construct($basePath, $funcs, $name = 'WPSP Artisan', $version = '1.0.0') {
		$this->basePath = rtrim($basePath, '/\\');
		$this->name     = $name;
		$this->funcs    = $funcs;
		$this->version  = $version;
		$this->useColor = getenv('NO_COLOR') === false && (!function_exists('stream_isatty') || @stream_isatty(STDOUT));

		$this->registerBaseBindings();

		$this->load(__DIR__ . '/../Console/Commands');
		$this->load($this->basePath . '/app/Console/Commands');
	}

	/**
	 * Đăng ký chính Application vào container.
	 * => make('app'), make(Container::class), make(Application::class) đều trả về $this.
	 */
	public function registerBaseBindings() {
		static::setInstance($this);

		$this->instance('app', $this);
		$this->alias('app', Container::class);
		$this->alias('app', self::class);
		if (static::class !== self::class) {
			$this->alias('app', static::class);
		}
	}

	public function basePath($path = '') {
		return $path ? $this->basePath . '/' . ltrim($path, '/\\') : $this->basePath;
	}

	/*
	 * ---
	 * Autoload & discovery.
	 * ---
	 */

	/**
	 * Đăng ký autoloader PSR-4 đơn giản: $prefix => $dir.
	 */
	public static function autoload($prefix, $dir) {
		$prefix = trim($prefix, '\\') . '\\';
		$dir    = rtrim($dir, '/\\');

		spl_autoload_register(function($class) use ($prefix, $dir) {
			if (strpos($class, $prefix) !== 0) return;
			$file = $dir . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
			if (is_file($file)) require_once $file;
		});
	}

	/**
	 * Quét đệ quy thư mục, tìm mọi class kế thừa Command và đăng ký.
	 */
	/**
	 * Thêm class cần bỏ qua từ bên ngoài, vd: $app->except(['FooCommand'])->load(...)
	 */
	public function except(array $classes) {
		$this->except = array_merge($this->except, $classes);
		return $this;
	}

	public function isExcepted($class) {
		$shortName = substr(strrchr('\\' . $class, '\\'), 1);

		return in_array($shortName, $this->except, true)
			|| in_array(ltrim($class, '\\'), $this->except, true);
	}

	public function load($dir) {
		if (!is_dir($dir)) return $this;

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
		);

		$files = [];
		foreach ($iterator as $file) {
			if ($file->isFile() && $file->getExtension() === 'php') {
				$files[] = $file->getPathname();
			}
		}
		sort($files);

		foreach ($files as $file) {
			$class = $this->classFromFile($file);
			if (!$class) continue;

			// Bỏ qua trước khi require để file không bị nạp.
			if ($this->isExcepted($class)) continue;

			try {
				if (!class_exists($class, true)) {
					require_once $file;
				}
			}
			catch (\Throwable $e) {
				fwrite(STDERR, $this->color('[!] Could not load ' . $file . ': ' . $e->getMessage(), 'yellow') . PHP_EOL);
				continue;
			}
			if (!class_exists($class, false)) continue;

			$ref = new \ReflectionClass($class);
			if ($ref->isSubclassOf(Command::class) && $ref->isInstantiable()) {
				$this->add($this->make($class));
			}
		}

		return $this;
	}

	/**
	 * Đọc namespace + tên class từ file mà không cần include.
	 */
	public function classFromFile($file) {
		$code = file_get_contents($file);
		if (!preg_match('/^\s*(?:abstract\s+|final\s+|readonly\s+)*class\s+(\w+)/m', $code, $c)) {
			return null;
		}
		$ns = preg_match('/^\s*namespace\s+([\w\\\\]+)\s*;/m', $code, $n) ? $n[1] . '\\' : '';
		return $ns . $c[1];
	}

	public function add(Command $command) {
		$command->setApplication($this);
		$this->commands[$command->getName()] = $command;
		return $this;
	}

	public function all() {
		ksort($this->commands);
		return $this->commands;
	}

	/*
	 * ---
	 * Run.
	 * ---
	 */

	public function run(array $argv = null) {
		$argv   = $argv ?? $_SERVER['argv'];
		$tokens = array_slice($argv, 1);

		// Tách tên command (token đầu tiên không bắt đầu bằng "-").
		$name = null;
		foreach ($tokens as $i => $token) {
			if ($token === '--') break;
			if ($token === '' || $token[0] !== '-') {
				$name = $token;
				unset($tokens[$i]);
				break;
			}
		}
		$tokens = array_values($tokens);

		if (in_array('--no-ansi', $tokens, true)) {
			$this->useColor = false;
			$tokens         = array_values(array_diff($tokens, ['--no-ansi']));
		}
		if (in_array('--ansi', $tokens, true)) {
			$this->useColor = true;
			$tokens         = array_values(array_diff($tokens, ['--ansi']));
		}

		if (in_array('-V', $tokens, true) || in_array('--version', $tokens, true)) {
			echo $this->name . ' ' . $this->color($this->version, 'green') . PHP_EOL;
			return 0;
		}

		if ($name === null || $name === 'list') {
			$this->renderList($name === 'list' ? ($tokens[0] ?? null) : null);
			return 0;
		}

		// php artisan help make:admin-page
		if ($name === 'help') {
			$target = $tokens[0] ?? null;
			if (!$target) {
				$this->renderList();
				return 0;
			}
			$name   = $target;
			$tokens = ['--help'];
		}

		try {
			$command = $this->find($name);
			return $command->run($tokens);
		}
		catch (\Throwable $e) {
			$this->renderException($e);
			return 1;
		}
	}

	/**
	 * Tìm command theo tên, hỗ trợ viết tắt (vd: "m:a" => "make:admin-page").
	 */
	public function find($name) {
		if (isset($this->commands[$name])) {
			return $this->commands[$name];
		}

		// Viết tắt theo từng đoạn ngăn cách bởi ":" hoặc "-".
		$pattern = '/^' . implode('[^:]*:', array_map(function($part) {
				return preg_quote($part, '/');
			}, explode(':', $name))) . '/';

		$matches = array_values(array_filter(array_keys($this->commands), function($cmd) use ($pattern) {
			return preg_match($pattern, $cmd);
		}));

		if (count($matches) === 1) {
			return $this->commands[$matches[0]];
		}

		if (count($matches) > 1) {
			throw new \InvalidArgumentException("Command \"{$name}\" is ambiguous. Did you mean one of these?\n  " . implode("\n  ", $matches));
		}

		// Gợi ý theo độ giống.
		$suggest = [];
		foreach (array_keys($this->commands) as $cmd) {
			if (levenshtein($name, $cmd) <= strlen($name) / 3 || strpos($cmd, $name) !== false) {
				$suggest[] = $cmd;
			}
		}

		$message = "Command \"{$name}\" is not defined.";
		if ($suggest) {
			$message .= "\n\nDid you mean one of these?\n  " . implode("\n  ", $suggest);
		}
		throw new \InvalidArgumentException($message);
	}

	/*
	 * ---
	 * Output.
	 * ---
	 */

	public function color($text, $color) {
		if (!$this->useColor) return $text;
		$codes = ['red' => '31', 'green' => '32', 'yellow' => '33', 'blue' => '34', 'gray' => '90', 'bold' => '1', 'error' => '37;41'];
		return isset($codes[$color]) ? "\033[{$codes[$color]}m{$text}\033[0m" : $text;
	}

	public function renderList($namespace = null) {
		echo $this->name . ' ' . $this->color($this->version, 'green') . PHP_EOL . PHP_EOL;

		echo $this->color('Usage:', 'yellow') . PHP_EOL;
		echo '  command [options] [arguments]' . PHP_EOL . PHP_EOL;

		echo $this->color('Options:', 'yellow') . PHP_EOL;
		$globals = [
			'-h, --help'           => 'Display help for the given command',
			'-V, --version'        => 'Display this application version',
			'    --ansi|--no-ansi' => 'Force (or disable) ANSI output',
			'-n, --no-interaction' => 'Do not ask any interactive question',
		];
		foreach ($globals as $label => $desc) {
			echo '  ' . $this->color(str_pad($label, 24), 'green') . $desc . PHP_EOL;
		}
		echo PHP_EOL;

		$builtin = ['help' => 'Display help for a command', 'list' => 'List commands'];
		$groups  = ['' => []];
		foreach ($this->all() as $name => $command) {
			if ($command->isHidden()) continue;
			$group                 = strpos($name, ':') !== false ? strstr($name, ':', true) : '';
			$groups[$group][$name] = $command->getDescription();
		}
		if ($namespace === null) {
			$groups[''] = $builtin + $groups[''];
		}
		ksort($groups);

		$names = array_merge(array_keys($builtin), array_keys($this->commands));
		$width = max(array_map('strlen', $names)) + 2;

		echo $this->color($namespace ? "Available commands for the \"{$namespace}\" namespace:" : 'Available commands:', 'yellow') . PHP_EOL;
		foreach ($groups as $group => $items) {
			if (!$items || ($namespace !== null && $group !== $namespace)) continue;
			if ($group !== '' && $namespace === null) {
				echo ' ' . $this->color($group, 'yellow') . PHP_EOL;
			}
			ksort($items);
			foreach ($items as $name => $desc) {
				echo '  ' . $this->color(str_pad($name, $width), 'green') . $desc . PHP_EOL;
			}
		}
	}

	public function renderException(\Throwable $e) {
		$lines = explode("\n", $e->getMessage());
		$width = max(array_map('mb_strlen', $lines)) + 4;

		fwrite(STDERR, PHP_EOL);
		fwrite(STDERR, '  ' . $this->color(str_repeat(' ', $width), 'error') . PHP_EOL);
		foreach ($lines as $line) {
			fwrite(STDERR, '  ' . $this->color('  ' . $line . str_repeat(' ', $width - mb_strlen($line) - 2), 'error') . PHP_EOL);
		}
		fwrite(STDERR, '  ' . $this->color(str_repeat(' ', $width), 'error') . PHP_EOL . PHP_EOL);

		if (!($e instanceof \InvalidArgumentException) || getenv('XCONSOLE_DEBUG')) {
			fwrite(STDERR, $this->color('  at ' . $e->getFile() . ':' . $e->getLine(), 'gray') . PHP_EOL . PHP_EOL);
		}
	}

}