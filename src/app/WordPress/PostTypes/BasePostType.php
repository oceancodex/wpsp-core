<?php

namespace WPSPCORE\App\WordPress\PostTypes;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

abstract class BasePostType extends BaseInstances {

	use ObjectToArrayTrait;

	public $post_type         = null;
	public $args              = [];
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'] ?? null;
		$this->overridePostType($this->extraParams['full_path'] ?? null);
		$this->prepareArguments();
	}

	public function afterInstanceConstruct() {
		$this->prepareArgumentsAfterCustomProperties();
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

	protected function prepareArguments() {
		$this->args = new PostTypeData($this);

		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
			}
			if (array_key_exists($key, $this->args->labels)) {
				$this->args->labels[$key] = $value;
			}
		}
	}

	protected function prepareArgumentsAfterCustomProperties() {
		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
			}
			if (array_key_exists($key, $this->args->labels)) {
				$this->args->labels[$key] = $value;
			}
		}

		// Unset post_type from args.
		unset($this->args->post_type);
		unset($this->args->previousArgs);
	}

}