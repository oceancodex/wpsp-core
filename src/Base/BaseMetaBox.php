<?php

namespace WPSPCORE\Base;

abstract class BaseMetaBox extends BaseInstances {

	public $id                = null;
	public $title             = 'Custom Meta Box';
	public $screen            = 'post';
	public $context           = 'advanced';
	public $priority          = 'default';
	public $callback_args     = null;
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'] ?? null;
		$this->overrideId($this->extraParams['id'] ?? null);
		$this->customProperties();
	}

	private function overrideId($id = null) {
		if ($id && !$this->id) {
			$this->id = $id;
		}
	}

	/*
	 *
	 */

	public function init($post_type = null, $post = null) {
		if ($this->id) {
			add_meta_box(
				$this->id,
				$this->title,
				[$this, $this->callback_function],
				$this->screen,
				$this->context,
				$this->priority,
				array_merge($this->callback_args ?? [], ['post_type' => $post_type, 'post' => $post])
			);
		}
	}

	/*
	 *
	 */

//	abstract public function index($post, $meta_box);

	abstract public function customProperties();

}