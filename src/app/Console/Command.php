<?php
/**
 * Created by PhpStorm.
 * User: Khanh
 * Date: 05/10/2026
 * Time: 8:56 CH
 */

namespace WPSPCORE\App\Console;

use WPSPCORE\App\App\Application;

if (class_exists('Illuminate\Console\Command')) {
	abstract class Command extends \Illuminate\Console\Command {}
}
else {
	/**
	 * Base command - mô phỏng Illuminate\Console\Command bằng PHP thuần.
	 *
	 * Hỗ trợ cú pháp $signature giống Laravel:
	 *   {name}          argument bắt buộc
	 *   {name?}         argument tuỳ chọn
	 *   {name=foo}      argument tuỳ chọn có giá trị mặc định
	 *   {name*}         argument dạng mảng (bắt buộc), {name?*} (tuỳ chọn)
	 *   {--flag}        option dạng cờ (true/false)
	 *   {--opt=}        option nhận giá trị (mặc định null)
	 *   {--opt=foo}     option nhận giá trị, mặc định "foo"
	 *   {--opt=*}       option nhận nhiều giá trị
	 *   {--P|opt=}      option có shortcut -P
	 *   {... : Mô tả}   mô tả cho argument/option
	 */
	abstract class Command {

		protected $signature   = '';
		protected $description = '';
		protected $hidden      = false;

		/** @var Application */
		protected $app;

		/** @var Application Tương thích code cũ: $this->laravel->make(...) */
		protected $laravel;

		protected $name;
		protected $argumentDefs = [];
		protected $optionDefs   = [];
		protected $shortcuts    = [];

		protected $arguments = [];
		protected $options   = [];

		public function __construct() {
			$this->parseSignature($this->signature);
		}

		/*
		 * Logic chính đặt trong handle(). Không khai báo abstract để command con
		 * có thể type-hint dependency: public function handle(Funcs $funcs) { ... }
		 * Trả về int (exit code) hoặc null.
		 */

		/*
		 * ---
		 * Getters.
		 * ---
		 */

		public function getName() {
			return $this->name;
		}

		public function getDescription() {
			return $this->description;
		}

		public function isHidden() {
			return (bool)$this->hidden;
		}

		public function setApplication(Application $app) {
			$this->app = $this->laravel = $app;
			return $this;
		}

		public function getApplication() {
			return $this->app;
		}

		public function getLaravel() {
			return $this->app;
		}

		/**
		 * Lấy service từ container: $this->make('funcs')
		 */
		public function make($abstract, array $parameters = []) {
			return $this->app->make($abstract, $parameters);
		}

		/**
		 * Đường dẫn gốc của plugin (thư mục chứa file artisan).
		 */
		public function basePath($path = '') {
			$base = $this->app ? $this->app->basePath() : getcwd();
			return $path ? $base . '/' . ltrim($path, '/') : $base;
		}

		/*
		 * ---
		 * Signature parser.
		 * ---
		 */

		protected function parseSignature($signature) {
			$signature = trim($signature);

			if (!preg_match('/^[^\s{]+/', $signature, $m)) {
				throw new \LogicException('Unable to determine command name from signature in ' . static::class);
			}
			$this->name = $m[0];

			preg_match_all('/\{\s*(.*?)\s*\}/s', $signature, $matches);

			foreach ($matches[1] as $token) {
				$desc = '';
				if (preg_match('/^(.*?)\s+:\s*(.*)$/s', $token, $parts)) {
					$token = trim($parts[1]);
					$desc  = trim(preg_replace('/\s+/', ' ', $parts[2]));
				}

				if (strpos($token, '--') === 0) {
					$this->addOptionDef(substr($token, 2), $desc);
				}
				else {
					$this->addArgumentDef($token, $desc);
				}
			}
		}

		protected function addArgumentDef($token, $desc) {
			$def = ['description' => $desc, 'required' => true, 'array' => false, 'default' => null];

			if (substr($token, -2) === '?*') {
				$def['required'] = false;
				$def['array']    = true;
				$def['default']  = [];
				$token           = substr($token, 0, -2);
			}
			elseif (substr($token, -1) === '*') {
				$def['array'] = true;
				$token        = substr($token, 0, -1);
			}
			elseif (substr($token, -1) === '?') {
				$def['required'] = false;
				$token           = substr($token, 0, -1);
			}
			elseif (strpos($token, '=') !== false) {
				[$token, $default] = explode('=', $token, 2);
				$def['required'] = false;
				$def['default']  = $default === '' ? null : $default;
			}

			$this->argumentDefs[trim($token)] = $def;
		}

		protected function addOptionDef($token, $desc) {
			$def = ['description' => $desc, 'shortcut' => null, 'acceptValue' => false, 'array' => false, 'default' => false];

			if (preg_match('/^(\w+)\|(.+)$/', $token, $m)) {
				$def['shortcut'] = $m[1];
				$token           = $m[2];
			}

			if (substr($token, -2) === '=*') {
				$def['acceptValue'] = true;
				$def['array']       = true;
				$def['default']     = [];
				$token              = substr($token, 0, -2);
			}
			elseif (strpos($token, '=') !== false) {
				[$token, $default] = explode('=', $token, 2);
				$def['acceptValue'] = true;
				$def['default']     = $default === '' ? null : $default;
			}

			$token                    = trim($token);
			$this->optionDefs[$token] = $def;
			if ($def['shortcut']) {
				$this->shortcuts[$def['shortcut']] = $token;
			}
		}

		/*
		 * ---
		 * Input binding.
		 * ---
		 */

		/**
		 * Chạy command với danh sách token (argv sau tên command).
		 */
		public function run(array $tokens) {
			$this->bind($tokens);

			if ($this->option('help')) {
				$this->printHelp();
				return 0;
			}

			if (!method_exists($this, 'handle')) {
				throw new \LogicException('No handle() method defined in ' . static::class . '.');
			}

			// Inject dependency vào handle() qua container.
			$result = $this->app ? $this->app->call([$this, 'handle']) : $this->handle();

			return is_int($result) ? $result : 0;
		}

		protected function bind(array $tokens) {
			// Option toàn cục.
			$globals = [
				'help'           => ['description' => 'Display help for the given command', 'shortcut' => 'h', 'acceptValue' => false, 'array' => false, 'default' => false],
				'no-interaction' => ['description' => 'Do not ask any interactive question', 'shortcut' => 'n', 'acceptValue' => false, 'array' => false, 'default' => false],
			];
			foreach ($globals as $name => $def) {
				if (!isset($this->optionDefs[$name])) {
					$this->optionDefs[$name] = $def;
					if (!isset($this->shortcuts[$def['shortcut']])) {
						$this->shortcuts[$def['shortcut']] = $name;
					}
				}
			}

			foreach ($this->optionDefs as $name => $def) {
				$this->options[$name] = $def['default'];
			}

			$positional = [];
			$parseOpts  = true;
			$count      = count($tokens);

			for ($i = 0; $i < $count; $i++) {
				$token = $tokens[$i];

				if ($parseOpts && $token === '--') {
					$parseOpts = false;
					continue;
				}

				// --long / --long=value / --long value
				if ($parseOpts && strpos($token, '--') === 0) {
					$name  = substr($token, 2);
					$value = null;
					if (strpos($name, '=') !== false) {
						[$name, $value] = explode('=', $name, 2);
					}
					$this->setOption($name, $value, $tokens, $i);
					continue;
				}

				// -s / -s value / -svalue
				if ($parseOpts && strlen($token) > 1 && $token[0] === '-' && !is_numeric($token)) {
					$short = $token[1];
					$value = strlen($token) > 2 ? ltrim(substr($token, 2), '=') : null;
					if (!isset($this->shortcuts[$short])) {
						throw new \InvalidArgumentException("The \"-{$short}\" option does not exist.");
					}
					$this->setOption($this->shortcuts[$short], $value, $tokens, $i);
					continue;
				}

				$positional[] = $token;
			}

			// Gán argument theo thứ tự.
			foreach ($this->argumentDefs as $name => $def) {
				if ($def['array']) {
					$this->arguments[$name] = $positional ?: $def['default'];
					$positional             = [];
				}
				else {
					$this->arguments[$name] = $positional ? array_shift($positional) : $def['default'];
				}
			}

			if ($positional) {
				throw new \InvalidArgumentException('Too many arguments, expected arguments "' . implode('" "', array_keys($this->argumentDefs)) . '".');
			}

			// Kiểm tra argument bắt buộc (bỏ qua khi xem help).
			if (!$this->options['help']) {
				$missing = [];
				foreach ($this->argumentDefs as $name => $def) {
					if ($def['required'] && ($this->arguments[$name] === null || $this->arguments[$name] === [])) {
						$missing[] = $name;
					}
				}
				if ($missing) {
					throw new \InvalidArgumentException('Not enough arguments (missing: "' . implode(', ', $missing) . '").');
				}
			}
		}

		protected function setOption($name, $value, array $tokens, &$i) {
			if (!isset($this->optionDefs[$name])) {
				throw new \InvalidArgumentException("The \"--{$name}\" option does not exist.");
			}

			$def = $this->optionDefs[$name];

			if (!$def['acceptValue']) {
				if ($value !== null) {
					throw new \InvalidArgumentException("The \"--{$name}\" option does not accept a value.");
				}
				$this->options[$name] = true;
				return;
			}

			// Lấy giá trị từ token kế tiếp: --parent foo
			if ($value === null && isset($tokens[$i + 1]) && ($tokens[$i + 1] === '' || $tokens[$i + 1][0] !== '-')) {
				$value = $tokens[++$i];
			}

			if ($def['array']) {
				if ($value !== null) {
					$this->options[$name][] = $value;
				}
			}
			else {
				$this->options[$name] = $value;
			}
		}

		/*
		 * ---
		 * Input accessors.
		 * ---
		 */

		public function argument($key = null) {
			if ($key === null) return $this->arguments;
			if (!array_key_exists($key, $this->argumentDefs)) {
				throw new \InvalidArgumentException("The \"{$key}\" argument does not exist.");
			}
			return $this->arguments[$key] ?? null;
		}

		public function arguments() {
			return $this->arguments;
		}

		public function option($key = null) {
			if ($key === null) return $this->options;
			if (!array_key_exists($key, $this->optionDefs)) {
				throw new \InvalidArgumentException("The \"{$key}\" option does not exist.");
			}
			return $this->options[$key] ?? null;
		}

		public function options() {
			return $this->options;
		}

		public function hasArgument($key) {
			return array_key_exists($key, $this->argumentDefs);
		}

		public function hasOption($key) {
			return array_key_exists($key, $this->optionDefs);
		}

		/*
		 * ---
		 * Interactive.
		 * ---
		 */

		protected function interactive() {
			return !$this->option('no-interaction') && (!function_exists('stream_isatty') || @stream_isatty(STDIN) || getenv('XCONSOLE_FORCE_INTERACTIVE'));
		}

		protected function readLine($prompt) {
			// In phần câu hỏi (có màu, có xuống dòng) bằng echo,
			// chỉ đưa dòng prompt cuối cùng cho readline().
			$pos = strrpos($prompt, "\n");
			if ($pos !== false) {
				echo substr($prompt, 0, $pos + 1);
				$prompt = substr($prompt, $pos + 1);
			}

			// readline() đếm cả byte mã màu ANSI => lệch con trỏ (rõ nhất trên Windows).
			$prompt = preg_replace('/\033\[[0-9;]*m/', '', $prompt);

			if (function_exists('readline') && @stream_isatty(STDIN)) {
				$line = readline($prompt);
				return $line === false ? null : $line;
			}

			echo $prompt;
			$line = fgets(STDIN);
			return $line === false ? null : rtrim($line, "\r\n");
		}

		public function ask($question, $default = null) {
			if (!$this->interactive()) return $default;

			$hint   = $default !== null && $default !== '' ? ' [' . $this->color($default, 'yellow') . ']' : '';
			$answer = $this->readLine($this->color(" {$question}", 'green') . "{$hint}:\n > ");
			$answer = $answer === null ? '' : trim($answer);

			return $answer === '' ? $default : $answer;
		}

		public function secret($question) {
			if (!$this->interactive()) return null;

			echo $this->color(" {$question}", 'green') . ":\n > ";
			$hide = DIRECTORY_SEPARATOR === '/' && @stream_isatty(STDIN);
			if ($hide) shell_exec('stty -echo');
			$line = fgets(STDIN);
			if ($hide) {
				shell_exec('stty echo');
				echo PHP_EOL;
			}

			return $line === false ? null : rtrim($line, "\r\n");
		}

		public function confirm($question, $default = false) {
			if (!$this->interactive()) return $default;

			$hint   = $default ? 'yes' : 'no';
			$answer = $this->readLine($this->color(" {$question} (yes/no)", 'green') . ' [' . $this->color($hint, 'yellow') . "]:\n > ");
			$answer = strtolower(trim((string)$answer));

			if ($answer === '') return $default;
			return in_array($answer, ['y', 'yes', '1', 'true', 'có', 'co'], true);
		}

		public function choice($question, array $choices, $default = null) {
			if (!$this->interactive()) {
				return is_int($default) ? ($choices[$default] ?? null) : $default;
			}

			$defaultLabel = is_int($default) ? ($choices[$default] ?? null) : $default;
			$hint         = $defaultLabel !== null ? ' [' . $this->color($defaultLabel, 'yellow') . ']' : '';
			echo $this->color(" {$question}", 'green') . "{$hint}:\n";
			foreach ($choices as $key => $label) {
				echo "  [" . $this->color($key, 'yellow') . "] {$label}\n";
			}

			while (true) {
				$answer = trim((string)$this->readLine(' > '));
				if ($answer === '' && $defaultLabel !== null) return $defaultLabel;
				if (array_key_exists($answer, $choices)) return $choices[$answer];
				if (in_array($answer, $choices, true)) return $answer;
				$this->error("Value \"{$answer}\" is invalid.");
			}
		}

		/*
		 * ---
		 * Output.
		 * ---
		 */

		public function color($text, $color) {
			return $this->app ? $this->app->color($text, $color) : $text;
		}

		public function line($text = '', $color = null) {
			echo ($color ? $this->color($text, $color) : $text) . PHP_EOL;
		}

		public function info($text) {
			$this->line($text, 'green');
		}

		public function comment($text) {
			$this->line($text, 'yellow');
		}

		public function warn($text) {
			$this->line($text, 'yellow');
		}

		public function error($text) {
			fwrite(STDERR, $this->color($text, 'red') . PHP_EOL);
		}

		public function newLine($count = 1) {
			echo str_repeat(PHP_EOL, $count);
		}

		public function table(array $headers, array $rows) {
			$rows   = array_map('array_values', $rows);
			$widths = array_map('mb_strlen', $headers);
			foreach ($rows as $row) {
				foreach ($row as $i => $cell) {
					$widths[$i] = max($widths[$i] ?? 0, mb_strlen((string)$cell));
				}
			}

			$sep = '+' . implode('+', array_map(function($w) {
					return str_repeat('-', $w + 2);
				}, $widths)) . '+';
			$fmt = function($row) use ($widths) {
				$cells = [];
				foreach ($widths as $i => $w) {
					$cell    = (string)($row[$i] ?? '');
					$cells[] = ' ' . $cell . str_repeat(' ', $w - mb_strlen($cell)) . ' ';
				}
				return '|' . implode('|', $cells) . '|';
			};

			$this->line($sep);
			$this->line($fmt($headers));
			$this->line($sep);
			foreach ($rows as $row) $this->line($fmt($row));
			$this->line($sep);
		}

		/*
		 * ---
		 * Help.
		 * ---
		 */

		public function printHelp() {
			$this->line($this->color('Description:', 'yellow'));
			$this->line('  ' . $this->description);
			$this->newLine();

			$usage = $this->name;
			if ($this->optionDefs) $usage .= ' [options]';
			if ($this->argumentDefs) {
				$usage .= ' [--]';
				foreach ($this->argumentDefs as $name => $def) {
					$part  = "<{$name}>" . ($def['array'] ? '...' : '');
					$usage .= ' ' . ($def['required'] ? $part : "[{$part}]");
				}
			}
			$this->line($this->color('Usage:', 'yellow'));
			$this->line('  ' . $usage);
			$this->newLine();

			if ($this->argumentDefs) {
				$this->line($this->color('Arguments:', 'yellow'));
				$width = max(array_map('strlen', array_keys($this->argumentDefs))) + 2;
				foreach ($this->argumentDefs as $name => $def) {
					$default = $def['default'] !== null && $def['default'] !== [] ? $this->color(' [default: "' . (is_array($def['default']) ? implode(',', $def['default']) : $def['default']) . '"]', 'yellow') : '';
					$this->line('  ' . $this->color(str_pad($name, $width), 'green') . $def['description'] . $default);
				}
				$this->newLine();
			}

			$this->line($this->color('Options:', 'yellow'));
			$labels = [];
			foreach ($this->optionDefs as $name => $def) {
				$label = ($def['shortcut'] ? "-{$def['shortcut']}, " : '    ') . "--{$name}";
				if ($def['acceptValue']) $label .= '[=' . strtoupper($name) . ']';
				if ($def['array']) $label .= ' (multiple values allowed)';
				$labels[$name] = $label;
			}
			$width = max(array_map('strlen', $labels)) + 2;
			foreach ($this->optionDefs as $name => $def) {
				$default = $def['acceptValue'] && $def['default'] !== null && $def['default'] !== [] ? $this->color(' [default: "' . $def['default'] . '"]', 'yellow') : '';
				$this->line('  ' . $this->color(str_pad($labels[$name], $width), 'green') . $def['description'] . $default);
			}
		}

	}

}