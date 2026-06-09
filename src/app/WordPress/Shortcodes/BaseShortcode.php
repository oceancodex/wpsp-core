<?php

namespace WPSPCORE\App\WordPress\Shortcodes;

use WPSPCORE\BaseInstances;

abstract class BaseShortcode extends BaseInstances {

	public  $shortcode         = null;

	public  $callback_function = null;

	private $path              = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideShortcode($this->extraParams['full_path']);
		$this->path = $this->extraParams['path'];
	}

	/*
	 *
	 */

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overrideShortcode($shortcode = null) {
		if ($shortcode && !$this->shortcode) {
			$this->shortcode = $shortcode;
		}
	}

	/*
	 *
	 */

	public function init($shortcode = null) {
		$shortcode = $this->shortcode ?? $shortcode;

		if ($shortcode) {
			// Register shortcode with dependency injection.
			add_shortcode($shortcode, function($atts, $content, $tag) use ($shortcode) {
				return $this->autoResolveAndCall(
					$this->path,
					$shortcode,
					$this->request->getRequestUri(),
					$this,
					$this->callback_function,
					[
						'atts'    => $atts,
						'content' => $content,
						'tag'     => $tag,
					]
				);
			});
		}
	}

}