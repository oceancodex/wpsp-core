<?php

namespace WPSPCORE\App\WordPress\Shortcodes;

use WPSPCORE\BaseInstances;

abstract class BaseShortcode extends BaseInstances {

	private $path              = null;

	public  $shortcode         = null;
	public  $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->path = $this->extraParams['path'];
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideShortcode($this->extraParams['full_path']);
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

	/*
	 *
	 */

	protected function overrideShortcode($shortcode = null) {
		if ($shortcode && !$this->shortcode) {
			$this->shortcode = $shortcode;
		}
	}

}