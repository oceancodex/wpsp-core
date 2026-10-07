<?php

namespace WPSPCORE\App\Widen\Lite\Http;

use RuntimeException;
use SplFileInfo;

/**
 * File upload - mô phỏng Illuminate\Http\UploadedFile (Symfony UploadedFile + File).
 *
 * Kế thừa SplFileInfo: getRealPath(), getFilename(), getExtension(), (string)$file ...
 * $test = true cho phép move() file không đến từ HTTP upload (test, file tự tạo).
 */
class UploadedFile extends SplFileInfo {

	protected static array $mimeExtensions = [
		'image/jpeg'                                                                => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
		'image/avif'                                                                => 'avif', 'image/bmp' => 'bmp', 'image/svg+xml' => 'svg', 'image/x-icon' => 'ico',
		'image/vnd.microsoft.icon'                                                  => 'ico', 'image/tiff' => 'tiff', 'image/heic' => 'heic',
		'video/mp4'                                                                 => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov', 'video/x-msvideo' => 'avi',
		'audio/mpeg'                                                                => 'mp3', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav', 'audio/ogg' => 'ogg',
		'application/pdf'                                                           => 'pdf', 'application/zip' => 'zip', 'application/x-rar-compressed' => 'rar',
		'application/x-7z-compressed'                                               => '7z', 'application/gzip' => 'gz', 'application/x-tar' => 'tar',
		'application/json'                                                          => 'json', 'application/xml' => 'xml', 'text/xml' => 'xml',
		'text/plain'                                                                => 'txt', 'text/csv' => 'csv', 'text/html' => 'html', 'text/css' => 'css',
		'application/msword'                                                        => 'doc', 'application/vnd.ms-excel' => 'xls', 'application/vnd.ms-powerpoint' => 'ppt',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
	];

	protected ?string $detectedMime = null;
	protected bool    $moved        = false;

	public function __construct(
		string $path,
		protected string $originalName,
		protected ?string $mimeType = null,
		protected int $error = UPLOAD_ERR_OK,
		protected int $size = 0,
		protected bool $test = false
	) {
		parent::__construct($path);
		$this->originalName = static::sanitizeName($originalName);
	}

	/** Tạo từ một file có sẵn trên đĩa (không phải HTTP upload). */
	public static function createFromPath(string $path, ?string $originalName = null, ?string $mimeType = null): static {
		return new static($path, $originalName ?? basename($path), $mimeType, UPLOAD_ERR_OK, is_file($path) ? (int)filesize($path) : 0, true);
	}

	/** Tạo file tạm để test: UploadedFile::fake('avatar.jpg', 'nội dung') */
	public static function fake(string $name, string $content = ''): static {
		$path = tempnam(sys_get_temp_dir(), 'upl');
		file_put_contents($path, $content);
		return static::createFromPath($path, $name);
	}

	protected static function sanitizeName(string $name): string {
		$name = str_replace('\\', '/', $name);
		$pos  = strrpos($name, '/');
		return $pos === false ? $name : substr($name, $pos + 1);
	}

	/*
	 * ---
	 * Thông tin.
	 * ---
	 */

	/** Đường dẫn thực của file (Laravel: path()). */
	public function path(): string {
		return $this->getRealPath() ?: $this->getPathname();
	}

	public function getClientOriginalName(): string {
		return $this->originalName;
	}

	public function getClientOriginalPath(): string {
		return $this->originalName;
	}

	public function getClientOriginalExtension(): string {
		return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
	}

	public function clientExtension(): string {
		return $this->guessClientExtension() ?? $this->getClientOriginalExtension();
	}

	public function getClientMimeType(): ?string {
		return $this->mimeType;
	}

	/** MIME thật đọc từ nội dung file (cache sau lần đầu). */
	public function getMimeType(): ?string {
		if ($this->detectedMime === null && $this->isFile() && function_exists('finfo_open')) {
			static $finfo;
			$finfo              ??= finfo_open(FILEINFO_MIME_TYPE);
			$this->detectedMime = finfo_file($finfo, $this->getPathname()) ?: null;
		}
		return $this->detectedMime ?? $this->mimeType;
	}

	public function guessExtension(): ?string {
		return static::$mimeExtensions[$this->getMimeType()] ?? null;
	}

	public function guessClientExtension(): ?string {
		return static::$mimeExtensions[(string)$this->mimeType] ?? null;
	}

	/** Phần mở rộng theo nội dung thật, fallback phần mở rộng phía client. */
	public function extension(): string {
		return $this->guessExtension() ?? $this->getClientOriginalExtension();
	}

	public function getSize(): int|false {
		return $this->isFile() ? (int)filesize($this->getPathname()) : $this->size;
	}

	public function getError(): int {
		return $this->error;
	}

	public function isValid(): bool {
		$isOk = $this->error === UPLOAD_ERR_OK && !$this->moved;
		return $this->test ? $isOk && $this->isFile() : $isOk && is_uploaded_file($this->getPathname());
	}

	public function getErrorMessage(): string {
		$messages = [
			UPLOAD_ERR_INI_SIZE   => 'The file "%s" exceeds your upload_max_filesize ini directive (limit is %d KiB).',
			UPLOAD_ERR_FORM_SIZE  => 'The file "%s" exceeds the upload limit defined in your form.',
			UPLOAD_ERR_PARTIAL    => 'The file "%s" was only partially uploaded.',
			UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
			UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
			UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
			UPLOAD_ERR_EXTENSION  => 'File upload was stopped by a PHP extension.',
		];
		$message  = $messages[$this->error] ?? 'The file "%s" was not uploaded due to an unknown error.';
		return sprintf($message, $this->originalName, (int)(static::getMaxFilesize() / 1024));
	}

	/** Giới hạn upload tối đa (bytes) theo php.ini. */
	public static function getMaxFilesize(): int {
		$toBytes = function($value): int {
			$value = trim((string)$value);
			if ($value === '' || $value === '-1' || $value === '0') return PHP_INT_MAX;
			$num = (int)$value;
			switch (strtolower(substr($value, -1))) {
				case 'g':
					$num *= 1024;
				case 'm':
					$num *= 1024;
				case 'k':
					$num *= 1024;
			}
			return $num;
		};
		return min($toBytes(ini_get('post_max_size')), $toBytes(ini_get('upload_max_filesize')));
	}

	public function isImage(): bool {
		return str_starts_with((string)$this->getMimeType(), 'image/');
	}

	/** [width, height] cho ảnh, null nếu không đọc được. */
	public function dimensions(): ?array {
		if (!$this->isFile() || !($info = @getimagesize($this->getPathname()))) return null;
		return [$info[0], $info[1]];
	}

	public function hash(string $algo = 'md5'): string|false {
		return $this->isFile() ? hash_file($algo, $this->getPathname()) : false;
	}

	/** Nội dung file. */
	public function get(): string|false {
		return $this->isFile() ? file_get_contents($this->getPathname()) : false;
	}

	public function getContent(): string {
		$content = $this->get();
		if ($content === false) throw new RuntimeException(sprintf('Could not get the content of the file "%s".', $this->getPathname()));
		return $content;
	}

	/** Tên ngẫu nhiên + extension; truyền $path để nhận về "path/tên". */
	public function hashName(?string $path = null): string {
		$ext  = $this->extension();
		$name = bin2hex(random_bytes(20)) . ($ext !== '' ? '.' . $ext : '');
		return $path !== null && $path !== '' ? rtrim($path, '/\\') . '/' . $name : $name;
	}

	/*
	 * ---
	 * Thao tác file.
	 * ---
	 */

	/**
	 * Di chuyển file. Trả về file mới (Stringable => đường dẫn đích).
	 * $name null => tên ngẫu nhiên hashName().
	 */
	public function move(string $directory, ?string $name = null): static {
		if (!$this->isValid()) {
			throw new RuntimeException($this->moved ? 'The file has already been moved.' : $this->getErrorMessage());
		}

		$target = $this->getTargetPath($directory, $name ?? $this->hashName());
		$source = $this->getPathname();

		set_error_handler(function($type, $msg) use (&$error) {
			$error = $msg;
			return true;
		});
		try {
			$moved = $this->test ? rename($source, $target) : move_uploaded_file($source, $target);
		}
		finally {
			restore_error_handler();
		}

		if (!$moved) {
			throw new RuntimeException(sprintf('Could not move the file "%s" to "%s" (%s).', $source, $target, strip_tags((string)$error)));
		}

		@chmod($target, 0666 & ~umask());
		$this->moved = true;

		return $this->newFrom($target);
	}

	/** Sao chép file, giữ nguyên file gốc. Trả về file mới. */
	public function copy(string $directory, ?string $name = null): static {
		$this->ensureReadable();

		$target = $this->getTargetPath($directory, $name ?? $this->hashName());
		if (!@copy($this->getPathname(), $target)) {
			throw new RuntimeException(sprintf('Could not copy the file "%s" to "%s".', $this->getPathname(), $target));
		}
		@chmod($target, 0666 & ~umask());

		return $this->newFrom($target);
	}

	/** Đổi tên trong cùng thư mục. */
	public function rename(string $name): static {
		return $this->move($this->getPath(), $name);
	}

	/** Xoá file khỏi đĩa. */
	public function delete(): bool {
		$path = $this->getPathname();
		if (!is_file($path)) return false;

		$deleted = @unlink($path);
		if ($deleted) {
			clearstatcache(true, $path);
			$this->moved = true;
		}
		return $deleted;
	}

	public function exists(): bool {
		return is_file($this->getPathname());
	}

	/**
	 * Lưu bản sao vào thư mục với tên ngẫu nhiên (Laravel: store()). Trả về đường dẫn đích.
	 */
	public function store(string $directory): string {
		return $this->storeAs($directory, $this->hashName());
	}

	/** Lưu bản sao vào thư mục với tên chỉ định (Laravel: storeAs()). */
	public function storeAs(string $directory, ?string $name = null): string {
		return $this->copy($directory, $name ?? $this->hashName())->getPathname();
	}

	protected function ensureReadable(): void {
		if ($this->error !== UPLOAD_ERR_OK) throw new RuntimeException($this->getErrorMessage());
		if (!is_readable($this->getPathname())) {
			throw new RuntimeException(sprintf('The file "%s" does not exist or is not readable.', $this->getPathname()));
		}
	}

	protected function getTargetPath(string $directory, string $name): string {
		if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
			throw new RuntimeException(sprintf('Unable to create the "%s" directory.', $directory));
		}
		if (!is_writable($directory)) {
			throw new RuntimeException(sprintf('Unable to write in the "%s" directory.', $directory));
		}
		return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . static::sanitizeName($name);
	}

	protected function newFrom(string $target): static {
		clearstatcache(true, $target);
		return new static($target, basename($target), $this->mimeType, UPLOAD_ERR_OK, (int)filesize($target), true);
	}

}