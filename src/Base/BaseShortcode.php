<?php

namespace WPSPCORE\Base;

abstract class BaseShortcode extends BaseInstances {

	abstract public function init($atts, $content, $tag);

}