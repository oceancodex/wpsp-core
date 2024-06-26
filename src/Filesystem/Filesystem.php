<?php

namespace WPSPCORE\Filesystem;

class Filesystem {

	private static ?\Illuminate\Filesystem\Filesystem $instance = null;

	public static function instance(): ?\Illuminate\Filesystem\Filesystem {
		if (!self::$instance) {
			self::$instance = new \Illuminate\Filesystem\Filesystem();
		}
		return self::$instance;
	}

	public static function exists($path): bool {
		return self::instance()->exists($path);
	}

	public static function missing($path): bool {
		return !self::instance()->exists($path);
	}

	public static function get($path, $lock = false): ?string {
		try {
			return self::instance()->get($path, $lock);
		}
		catch (\Illuminate\Contracts\Filesystem\FileNotFoundException|\Exception $e) {
			return null;
		}
	}

	public static function put($path, $contents, $lock = false): bool {
		return self::instance()->put($path, $contents, $lock);
	}

	public static function append($path, $data, $lock = false): int {
		return self::instance()->append($path, $data, $lock);
	}

	public static function replace($path, $content, $mode = null): void {
		self::instance()->replace($path, $content, $mode);
	}

	public static function prepend($path, $data): int {
        return self::instance()->prepend($path, $data);
    }

	public static function replaceInFile($search, $replace, $path): void {
		self::instance()->replaceInFile($search, $replace, $path);
	}

	public static function delete($paths): bool {
		return self::instance()->delete($paths);
	}

	public static function move($from, $to): bool {
		return self::instance()->move($from, $to);
	}

	public static function copy($from, $to): bool {
		return self::instance()->copy($from, $to);
	}

	public static function size($path): int {
		return self::instance()->size($path);
	}

	public static function lastModified($path): int {
		return self::instance()->lastModified($path);
	}

	public static function mimeType($path): string {
		return self::instance()->mimeType($path);
	}

	public static function extension($path): string {
		return self::instance()->extension($path);
	}

	public static function type($path): string {
		return self::instance()->type($path);
	}

	public static function allFiles($directory, $hidden = false): array {
		return self::instance()->allFiles($directory, $hidden);
	}

	public static function directories($directory): array {
		return self::instance()->directories($directory);
	}

	public static function makeDirectory($path, $mode = 0755, $recursive = false, $force = false): bool {
		return self::instance()->makeDirectory($path, $mode, $recursive, $force);
	}

	public static function deleteDirectory($directory, $preserve = false): bool {
        return self::instance()->deleteDirectory($directory, $preserve);
    }

	public static function cleanDirectory($directory): bool {
        return self::instance()->cleanDirectory($directory);
    }

	public static function copyDirectory($directory, $destination, $options = null): bool {
        return self::instance()->copyDirectory($directory, $destination, $options);
    }

	public static function moveDirectory($directory, $destination, $options = null): bool {
        return self::instance()->moveDirectory($directory, $destination, $options);
    }

}