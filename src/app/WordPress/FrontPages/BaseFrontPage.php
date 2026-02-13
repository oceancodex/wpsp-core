<?php

namespace WPSPCORE\App\WordPress\FrontPages;

use WPSPCORE\App\Routes\RouteTrait;
use WPSPCORE\BaseInstances;

abstract class BaseFrontPage extends BaseInstances {

	use RouteTrait;

	public $path                     = null;
	public $fullPath                 = null;
	public $callback_function        = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overridePath($this->extraParams['path']);
		$this->overrideFullPath($this->extraParams['full_path']);
	}

	/*
	 *
	 */

	private function overridePath($path = null) {
		if ($path && !$this->path) {
			$this->path = $path;
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

		if ($path && $fullPath) {

			$requestPath = trim($this->request->getPathInfo(), '/\\');

			// Access URL that match rewrite rule.
			if (!is_admin()) {
				// Cần phải hook vào 'wp' để có thể truy cập được global $post.
				add_action('wp', function() use ($path, $fullPath, $requestPath) {
					try {
						$matched = preg_match('/^' . $this->funcs->_regexPath($fullPath) . '$/iu', $requestPath);
						if (!$matched) {
							$matched = preg_match('/^' . $fullPath . '$/iu', $requestPath);
						}
					}
					catch (\Throwable $e) {
						$matched = false;
					}

					if (!$matched) return;

					$callback = $this->prepareCallbackFunction($this->callback_function, $path, $fullPath);
					$this->resolveAndCall($callback);
				});
			}
		}
	}

}