<?php

namespace WPSPCORE\Base;

use WPSPCORE\Data\PostTypeData;
use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BasePostType extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public $post_type         = null;
	public $args              = null;
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'] ?? null;
		$this->overridePostType($this->extraParams['post_type'] ?? null);
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

	public function customProperties() {}

}