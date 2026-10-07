<?php

namespace WPSPCORE\App\Widen\Lite\Http;

/**
 * $request->files: accepts raw $_FILES entries (including nested/multiple)
 * or UploadedFile objects, and stores only UploadedFile instances.
 * Inputs with no file chosen (UPLOAD_ERR_NO_FILE) are dropped.
 */
class FileBag extends ParameterBag {

	public function __construct(array $parameters = []) {
		parent::__construct();
		$this->replace($parameters);
	}

	public function replace(array $files = []): void {
		$this->parameters = [];
		$this->add($files);
	}

	public function set(string $key, mixed $value): void {
		$value = static::convert($value);
		if ($value === null || $value === []) {
			unset($this->parameters[$key]);
			return;
		}
		$this->parameters[$key] = $value;
	}

	public function add(array $files = []): void {
		foreach ($files as $key => $file) {
			$this->set((string)$key, $file);
		}
	}

	protected static function convert(mixed $value): UploadedFile|array|null {
		if ($value instanceof UploadedFile || $value === null) return $value;
		if (!is_array($value)) return null;

		if (isset($value['name']) && array_key_exists('tmp_name', $value)) {
			return static::convertFile($value);
		}

		$result = [];
		foreach ($value as $k => $item) {
			$converted = static::convert($item);
			if ($converted !== null && $converted !== []) $result[$k] = $converted;
		}
		return $result;
	}

	protected static function convertFile(array $file): UploadedFile|array|null {
		if (!is_array($file['name'])) {
			if ((int)($file['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_NO_FILE) return null;
			return new UploadedFile(
				(string)$file['tmp_name'], (string)$file['name'],
				$file['type'] ?? null, (int)($file['error'] ?? UPLOAD_ERR_OK), (int)($file['size'] ?? 0)
			);
		}

		$result = [];
		foreach (array_keys($file['name']) as $i) {
			$converted = static::convertFile([
				'name'     => $file['name'][$i],
				'type'     => $file['type'][$i] ?? null,
				'tmp_name' => $file['tmp_name'][$i],
				'error'    => $file['error'][$i] ?? UPLOAD_ERR_OK,
				'size'     => $file['size'][$i] ?? 0,
			]);
			if ($converted !== null && $converted !== []) $result[$i] = $converted;
		}
		return $result;
	}

}