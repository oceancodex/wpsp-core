<?php

namespace WPSPCORE\Base;

use WPSPCORE\Data\PostTypeData;
use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BasePostType extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public mixed $post_type = null;
	public mixed $args      = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $postType = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->overridePostType($postType);
		$this->prepareArguments();
		$this->customProperties();
		$this->prepareArguments($this->args);
	}

	/*
	 *
	 */

	public function init($postType = null): void {
		if ($this->post_type) {
			register_post_type($this->post_type, $this->args);
		}
	}

	/*
	 *
	 */

	protected function overridePostType($postType = null): void {
		if ($postType && !$this->post_type) {
			$this->post_type = $postType;
		}
	}

	protected function prepareArguments($args = null): void {
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