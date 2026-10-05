<?php

namespace WPSPCORE\App\Widen\Commons\Http;

use RuntimeException;

class UploadedFile {

	public function __construct(
		protected string $path,
		protected string $originalName,
		protected ?string $mimeType = null,
		protected int $error = UPLOAD_ERR_OK,
		protected int $size = 0
	) {}

	public function path(): string {
		return $this->path;
	}

	public function getClientOriginalName(): string {
		return $this->originalName;
	}

	public function getClientOriginalExtension(): string {
		return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
	}

	/** Extension guessed from the real file content, falls back to the client one. */
	public function extension(): string {
		$map = ['image/jpeg'      => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
		        'application/pdf' => 'pdf', 'text/plain' => 'txt', 'application/zip' => 'zip'];
		return $map[$this->getMimeType()] ?? $this->getClientOriginalExtension();
	}

	public function getClientMimeType(): ?string {
		return $this->mimeType;
	}

	/** Real MIME type detected from file content. */
	public function getMimeType(): ?string {
		if (!is_file($this->path)) return $this->mimeType;
		return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->path) ?: $this->mimeType;
	}

	public function getSize(): int {
		return is_file($this->path) ? (int)filesize($this->path) : $this->size;
	}

	public function getError(): int {
		return $this->error;
	}

	public function isValid(): bool {
		return $this->error === UPLOAD_ERR_OK && (is_uploaded_file($this->path) || is_file($this->path));
	}

	public function hashName(): string {
		return bin2hex(random_bytes(20)) . ($this->extension() ? '.' . $this->extension() : '');
	}

	/** Move to $directory; returns the final full path. */
	public function move(string $directory, ?string $name = null): string {
		if (!$this->isValid()) throw new RuntimeException('Upload failed, error code: ' . $this->error);
		if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
			throw new RuntimeException("Unable to create directory: {$directory}");
		}
		$target = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . ($name ?? $this->hashName());
		$moved  = is_uploaded_file($this->path) ? move_uploaded_file($this->path, $target) : rename($this->path, $target);
		if (!$moved) throw new RuntimeException("Unable to move file to: {$target}");
		@chmod($target, 0644);
		return $target;
	}

}