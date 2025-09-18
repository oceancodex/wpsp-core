<?php

namespace WPSPCORE\Base;

abstract class BaseMetaBox extends BaseInstances {

	private mixed $id                = null;
	private mixed $callback_function = null;
	public mixed  $title             = 'Custom Meta Box';
	public mixed  $screen            = 'post';
	public mixed  $context           = 'advanced';
	public mixed  $priority          = 'default';
	public mixed  $callback_args     = null;
	public mixed  $custom_properties = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $id = null, $callback_function = null, $custom_properties = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->id = $id;
		$this->callback_function = $callback_function;
		$this->custom_properties = $custom_properties;
		$this->customProperties();
	}

	/*
	 *
	 */

	public function init(string $post_type = null, \WP_Post $post = null): void {
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