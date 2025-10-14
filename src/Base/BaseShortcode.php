<?php

namespace WPSPCORE\Base;

abstract class BaseShortcode extends BaseInstances {

	public $shortcode = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv, $extraParams);
		$this->overrideShortcode($extraParams['shortcode']);
		$this->customProperties();
	}

	/*
	 *
	 */

	public function init($shortcode = null) {
		$callback  = $this->extraParams['callback_function'] ? [$this, $this->extraParams['callback_function']] : null;
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

	/*
	 *
	 */

//	abstract public function index($atts, $content, $tag);

	abstract public function customProperties();

}