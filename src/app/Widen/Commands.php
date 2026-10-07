<?php
/**
 * Created by PhpStorm.
 * User: Khanh
 * Date: 05/10/2026
 * Time: 8:56 CH
 */

namespace WPSPCORE\App\Widen;

use WPSPCORE\App\Console\Command;

/**
 * Console kernel - mô phỏng Illuminate\Foundation\Console\Kernel + Symfony Console Application.
 *
 * Lấy từ container: $app->make('commands') hoặc $app->make(Commands::class).
 * Chạy:             exit($app->handleCommand());
 * Gọi trong code:   $app->make('commands')->call('make:admin-page', ['name' => 'Foo', '--force' => true]);
 */
class Commands {

	/** @var Application */
	protected $app;

	/** name => Command */
	protected $commands = [];

	/** Thư mục / class / object chờ nạp khi bootstrap(). */
	protected $pending = [];

	protected $bootstrapped = false;

	public $useColor;

	public $except = [
		'KeyGenerateCommand',
		'ModelMakeCommand',
		'SeedCommand',
		'SeederMakeCommand',
		'WipeCommand',
	];

	public function __construct(Application $app) {
		$this->app      = $app;
		$this->useColor = getenv('NO_COLOR') === false && (!function_exists('stream_isatty') || @stream_isatty(STDOUT));

//		$this->pending[] = dirname(__DIR__, 2) . '/Console/Commands';
//		$this->pending[] = $app->path('Console/Commands');
	}

	public function getLaravel() {
		return $this->app;
	}

	/*
	 * ---
	 * Đăng ký.
	 * ---
	 */

	/**
	 * Bỏ qua command theo tên class ngắn hoặc FQCN. Gọi trước khi chạy.
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

	/**
	 * Thêm thư mục / class / object. Nạp lười cho tới khi cần.
	 */
	public function register($commands) {
		foreach ((array)$commands as $command) {
			$this->pending[] = $command;
		}

		if ($this->bootstrapped) {
			$this->loadPending();
		}

		return $this;
	}

	public function bootstrap() {
		if (!$this->bootstrapped) {
			$this->bootstrapped = true;
			$this->loadPending();
		}
		return $this;
	}

	protected function loadPending() {
		while ($this->pending) {
			$item = array_shift($this->pending);

			if ($item instanceof Command) {
				$this->add($item);
			}
			elseif (is_string($item) && is_dir($item)) {
				$this->load($item);
			}
			elseif (is_string($item) && class_exists($item) && !$this->isExcepted($item)) {
				$this->add($this->app->make($item));
			}
		}
	}

	/**
	 * Quét đệ quy thư mục, đăng ký mọi class kế thừa Command.
	 */
	public function load($dir) {
		if (!is_dir($dir)) return $this;

		$files    = [];
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
		foreach ($iterator as $file) {
			if ($file->isFile() && $file->getExtension() === 'php') {
				$files[] = $file->getPathname();
			}
		}
		sort($files);

		foreach ($files as $file) {
			$class = $this->classFromFile($file);

			// Bỏ qua trước khi require để file không bị nạp.
			if (!$class || $this->isExcepted($class)) continue;

			try {
				if (!class_exists($class)) {
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
				$this->add($this->app->make($class));
			}
		}

		return $this;
	}

	/**
	 * Đọc namespace + tên class từ file mà không include.
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
		$command->setLaravel($this->app);
		$command->setApplication($this);

		$this->commands[$command->getName()] = $command;
		return $this;
	}

	public function has($name) {
		return isset($this->bootstrap()->commands[$name]);
	}

	public function all() {
		$this->bootstrap();
		ksort($this->commands);
		return $this->commands;
	}

	/**
	 * Tìm command theo tên, hỗ trợ viết tắt (vd: "m:a" => "make:admin-page").
	 */
	public function find($name) {
		$this->bootstrap();

		if (isset($this->commands[$name])) {
			return $this->commands[$name];
		}

		$pattern = '/^' . implode('[^:]*:', array_map(function($part) {
				return preg_quote($part, '/');
			}, explode(':', $name))) . '/';

		$matches = array_values(preg_grep($pattern, array_keys($this->commands)));

		if (count($matches) === 1) {
			return $this->commands[$matches[0]];
		}

		if ($matches) {
			throw new \InvalidArgumentException("Command \"{$name}\" is ambiguous. Did you mean one of these?\n  " . implode("\n  ", $matches));
		}

		$suggest = array_filter(array_keys($this->commands), function($cmd) use ($name) {
			return levenshtein($name, $cmd) <= strlen($name) / 3 || strpos($cmd, $name) !== false;
		});

		$message = "Command \"{$name}\" is not defined.";
		if ($suggest) {
			$message .= "\n\nDid you mean one of these?\n  " . implode("\n  ", $suggest);
		}
		throw new \InvalidArgumentException($message);
	}

	/*
	 * ---
	 * Run.
	 * ---
	 */

	/**
	 * Chạy theo argv (mặc định $_SERVER['argv']). Trả về exit code.
	 */
	public function run(?array $argv = null) {
		$tokens = array_slice($argv ?? $_SERVER['argv'] ?? [], 1);

		// Tên command = token đầu tiên không bắt đầu bằng "-".
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

		foreach (['--no-ansi' => false, '--ansi' => true] as $flag => $value) {
			if (in_array($flag, $tokens, true)) {
				$this->useColor = $value;
				$tokens         = array_values(array_diff($tokens, [$flag]));
			}
		}

		if (in_array('-V', $tokens, true) || in_array('--version', $tokens, true)) {
			echo $this->app->name . ' ' . $this->color($this->app->version(), 'green') . PHP_EOL;
			return 0;
		}

		try {
			if ($name === null || $name === 'list') {
				$this->renderList($name === 'list' ? ($tokens[0] ?? null) : null);
				return 0;
			}

			// php artisan help make:admin-page
			if ($name === 'help') {
				if (!isset($tokens[0])) {
					$this->renderList();
					return 0;
				}
				$name   = $tokens[0];
				$tokens = ['--help'];
			}

			return $this->find($name)->run($tokens);
		}
		catch (\Throwable $e) {
			$this->renderException($e);
			return 1;
		}
	}

	/**
	 * Gọi command từ code, như Artisan::call().
	 * $parameters: ['name' => 'Foo', '--force' => true, '--tag' => ['a', 'b']]
	 */
	public function call($name, array $parameters = []) {
		return $this->find($name)->run($this->parametersToTokens($parameters));
	}

	protected function parametersToTokens(array $parameters) {
		$tokens = [];

		foreach ($parameters as $key => $value) {
			if (is_int($key) || strpos($key, '-') !== 0) {
				foreach ((array)$value as $v) $tokens[] = (string)$v;
				continue;
			}
			if ($value === true) {
				$tokens[] = $key;
				continue;
			}
			if ($value === false || $value === null) {
				continue;
			}
			foreach ((array)$value as $v) {
				$tokens[] = strpos($key, '--') === 0 ? $key . '=' . $v : $key . $v;
			}
		}

		return $tokens;
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
		echo $this->app->name . ' ' . $this->color($this->app->version(), 'green') . PHP_EOL . PHP_EOL;

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
		$groups  = ['' => $namespace === null ? $builtin : []];
		foreach ($this->all() as $name => $command) {
			if ($command->isHidden()) continue;
			$group                 = strpos($name, ':') !== false ? strstr($name, ':', true) : '';
			$groups[$group][$name] = $command->getDescription();
		}
		ksort($groups);

		$width = max(array_map('strlen', array_merge(array_keys($builtin), array_keys($this->commands)))) + 2;

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

		if (!($e instanceof \InvalidArgumentException) || getenv('XCONSOLE_DEBUG') || $this->app->hasDebugModeEnabled()) {
			fwrite(STDERR, $this->color('  at ' . $e->getFile() . ':' . $e->getLine(), 'gray') . PHP_EOL . PHP_EOL);
		}
	}

}