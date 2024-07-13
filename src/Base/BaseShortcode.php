<?php

namespace WPSPCORE\Base;

abstract class BaseShortcode extends BaseInstances {

	public function init($atts, $content, $tag) {
		return $this->index($atts, $content, $tag);
	}

	abstract public function index($atts, $content, $tag);

}