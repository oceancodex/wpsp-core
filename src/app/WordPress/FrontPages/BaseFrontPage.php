<?php

namespace WPSPCORE\App\WordPress\FrontPages;

use WPSPCORE\BaseInstances;

abstract class BaseFrontPage extends BaseInstances {

	public $path                     = null;
	public $fullPath                 = null;

	public $callback_function        = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideFullPath($this->extraParams['full_path'] ?? null);
		$this->overridePath($this->extraParams['path'] ?? null);
	}

	/*
	 *
	 */

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overridePath($path = null) {
		if ($path && !$this->path) {
			$this->path = $path;
		}
		elseif ($this->path) {
			$this->fullPath = $this->path;
		}
	}

	private function overrideFullPath($fullPath = null) {
		if ($fullPath && !$this->fullPath) {
			$this->fullPath = $fullPath;
		}
	}

	/*
	 *
	 */

	public function init($path = null, $fullPath = null) {
		$path     = $this->path ?? $path;
		$fullPath = $this->fullPath ?? $fullPath;

		/**
		 * Chuẩn hóa regex path
		 * - luôn có ^ ở đầu
		 * - luôn có $ ở cuối
		 */
		$regexPrefix = '^';
		$regexSuffix = '$';
		$regexPath   = $this->funcs->_regexPath($fullPath);
		$regexPath   = !str_starts_with($regexPath, $regexPrefix) ? $regexPrefix . $regexPath : $regexPath;
		$regexPath   = !str_ends_with($regexPath, $regexSuffix) ? $regexPath . $regexSuffix : $regexPath;

		$fullPathEx = !str_starts_with($fullPath, $regexPrefix) ? $regexPrefix . $fullPath : $fullPath;
		$fullPathEx = !str_ends_with($fullPathEx, $regexSuffix) ? $fullPathEx . $regexSuffix : $fullPathEx;

		if ($path && $fullPath) {
			$requestPath = ltrim($this->request->getRequestUri(), '/\\');

			// Access URL that match rewrite rule.
			if (!is_admin()) {
				// Cần phải hook vào 'wp' để có thể truy cập được global $post.
				add_action('wp', function() use ($path, $fullPath, $regexPath, $fullPathEx, $requestPath) {
					try {
						$matched = preg_match('/' . $regexPath . '/iu', $requestPath, $matches);
						if (!$matched) {
							$matched = preg_match('/' . $fullPathEx . '/iu', $requestPath, $matches);
						}
					}
					catch (\Throwable $e) {
						$matched = false;
					}

					if (!$matched) return;

					$callback = $this->prepareCallbackFunction($this->callback_function, $path, $fullPath);
					$this->resolveAndCall($callback);
				}, $this->extraParams['priority'] ?? 10, $this->extraParams['accepted_args'] ?? 1);
			}
		}
	}

}