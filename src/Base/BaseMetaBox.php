<?php

namespace WPSPCORE\Base;

abstract class BaseMetaBox extends BaseInstances {

	private $id                = null;
	private $callback_function = null;
	public  $title             = 'Custom Meta Box';
	public  $screen            = 'post';
	public  $context           = 'advanced';
	public  $priority          = 'default';
	public  $callback_args     = null;
	public  $custom_properties = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->id                = $this->customProperties['id'] ?? null;
		$this->callback_function = $this->customProperties['callback_function'] ?? null;;
		$this->custom_properties = $this->customProperties['custom_properties'] ?? [];
		$this->customProperties();
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