<?php

namespace WPSPCORE\Base;

abstract class BaseShortcode extends BaseInstances {

	abstract public function index($atts, $content, $tag);

}