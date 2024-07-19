<?php

namespace WPSPCORE\Base;

abstract class BaseMetaBox extends BaseInstances {

	private $id = null;

	public $title         = 'Custom Meta Box';
	public $screen        = 'post';
	public $context       = 'advanced';
	public $priority      = 'default';
	public $callback_args = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $id = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->id = $id;
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
				[$this, 'index'],
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

	abstract public function index($post, $meta_box);

	abstract public function customProperties();

}