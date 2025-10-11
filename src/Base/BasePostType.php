<?php

namespace WPSPCORE\Base;

use WPSPCORE\Data\PostTypeData;
use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BasePostType extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public $post_type         = null;
	public $args              = null;
	public $callback_function = null;
	public $custom_properties = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $postType = null, $callback_function = null, $custom_properties = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->callback_function = $callback_function;
		$this->custom_properties = $custom_properties;
		$this->overridePostType($postType);
		$this->prepareArguments();
		$this->customProperties();
		$this->prepareArguments($this->args);
	}

	/*
	 *
	 */

	public function init($postType = null) {
		$postType = $this->post_type ?? $postType;
		if ($postType) {
			register_post_type($postType, $this->args);
		}
	}

	/*
	 *
	 */

	protected function overridePostType($postType = null) {
		if ($postType && !$this->post_type) {
			$this->post_type = $postType;
		}
	}

	protected function prepareArguments($args = null) {
		$this->args = new PostTypeData($this, $args);
		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
//				unset($this->args->{$key});
			}
			if (array_key_exists($key, $this->args->labels) && !$args) {
				$this->args->labels[$key] = $value;
				unset($this->args->{$key});
			}
		}
		unset($this->args->post_type);
	}

	/*
	 *
	 */

	abstract public function customProperties();

}