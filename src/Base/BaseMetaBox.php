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

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $id = null, $callback_function = null, $custom_properties = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->id                = $id;
		$this->callback_function = $callback_function;
		$this->custom_properties = $custom_properties;
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