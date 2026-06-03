<?php

namespace WPSPCORE\App\WordPress\MetaBoxes;

use WPSPCORE\BaseInstances;

abstract class BaseMetaBox extends BaseInstances {

	public $id                = null;
	public $title             = 'Custom Meta Box';
	public $screen            = 'post';
	public $context           = 'advanced';
	public $priority          = 'default';
	public $callback_args     = [];
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'] ?? null;
		$this->overrideId($this->extraParams['full_path'] ?? null);
	}

	/*
	 *
	 */

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
			if ($this->screen == 'dashboard') {
				add_action('wp_dashboard_setup', function() use ($post_type, $post) {
					add_meta_box(
						$this->id,
						$this->title,
						function($post, $meta_box) {
							$requestPath = $this->request->getRequestUri();
							return $this->autoResolveAndCall($this->id, $this->extraParams['full_path'], $requestPath, $this, $this->callback_function, ['post' => $post, 'meta_box' => $meta_box]);
						},
						$this->screen,
						$this->context,
						$this->priority,
						array_merge($this->callback_args ?? [], ['post_type' => $post_type, 'post' => $post])
					);
				});
			}
			else {
				add_action('add_meta_boxes', function() use ($post_type, $post) {
					add_meta_box(
						$this->id,
						$this->title,
						function($post, $meta_box) {
							$requestPath = $this->request->getRequestUri();
							return $this->autoResolveAndCall($this->id, $this->extraParams['full_path'], $requestPath, $this, $this->callback_function, ['post' => $post, 'meta_box' => $meta_box]);
						},
						$this->screen,
						$this->context,
						$this->priority,
						array_merge($this->callback_args ?? [], ['post_type' => $post_type, 'post' => $post])
					);
				});
			}
		}
	}

}