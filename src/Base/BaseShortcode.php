<?php

namespace WPSPCORE\Base;

abstract class BaseShortcode extends BaseInstances {

	public $shortcode         = null;
	public $callback_function = null;
	public $custom_properties = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $shortcode = null, $callback_function = null, $custom_properties = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->callback_function = $callback_function;
		$this->custom_properties = $custom_properties;
		$this->overrideShortcode($shortcode);
		$this->customProperties();
	}

	/*
	 *
	 */

	public function init($shortcode = null) {
		$callback = $this->callback_function ? [$this, $this->callback_function] : null;
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