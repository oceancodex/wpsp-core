<?php

namespace WPSPCORE\App\WordPress\Shortcodes;

use WPSPCORE\BaseInstances;

abstract class BaseShortcode extends BaseInstances {

	public $shortcode         = null;
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideShortcode($this->extraParams['shortcode']);
	}

	/*
	 *
	 */

	public function init($shortcode = null) {
		$callback  = $this->callback_function ? [$this, $this->callback_function] : null;
		$shortcode = $this->shortcode ?? $shortcode;
		if ($shortcode) {
			add_shortcode($shortcode, $callback);
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