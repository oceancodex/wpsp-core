<?php

namespace WPSPCORE\App\File;

use Illuminate\Filesystem\Filesystem as IlluminateFile;
use WPSPCORE\BaseInstances;

if (class_exists('Illuminate\Support\Facades\File') || class_exists('Illuminate\Filesystem\Filesystem')) {
	/**
	 * @mixin \Illuminate\Support\Facades\File
	 */
	abstract class File extends BaseInstances {

		private ?IlluminateFile $facade;

		/*
		 *
		 */

		public function getFacade(): ?IlluminateFile {
			return $this->facade;
		}

		public function setFacade() {
			$this->facade = $this->funcs->_getApplication('files');
		}

		/*
		 *
		 */

		public function __call($method, $arguments) {
			return static::__callStatic($method, $arguments);
		}

		public static function __callStatic($method, $arguments) {
			$instance = static::wpspInstance();

			$underlineMethod = '_' . $method;
			if (method_exists($instance, $underlineMethod)) {
				return $instance->$underlineMethod(...$arguments);
			}

			return $instance->getFacade()?->$method(...$arguments);
		}

	}
}
else {
	class File {

		public static function exists($path) {
			return file_exists($path);
		}

		public static function isFile($path) {
			return is_file($path);
		}

		public static function isDirectory($path) {
			return is_dir($path);
		}

		public static function get($path) {
			if (!is_file($path)) {
				throw new \RuntimeException("File does not exist at path {$path}.");
			}
			return file_get_contents($path);
		}

		public static function put($path, $contents, $lock = false) {
			return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
		}

		public static function append($path, $data) {
			return file_put_contents($path, $data, FILE_APPEND);
		}

		public static function ensureDirectoryExists($path, $mode = 0755, $recursive = true) {
			if (!is_dir($path)) {
				mkdir($path, $mode, $recursive);
			}
		}

		public static function delete($paths) {
			$ok = true;
			foreach ((array)$paths as $path) {
				if (!@unlink($path)) $ok = false;
			}
			return $ok;
		}

		public static function deleteDirectory($dir) {
			if (!is_dir($dir)) return false;
			$items = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ($items as $item) {
				$item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
			}
			return rmdir($dir);
		}

		public static function files($dir) {
			return is_dir($dir) ? array_values(array_filter(glob(rtrim($dir, '/') . '/*'), 'is_file')) : [];
		}

		public static function copy($from, $to) {
			return copy($from, $to);
		}

	}
}