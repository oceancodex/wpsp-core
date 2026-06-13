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
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideId($this->extraParams['full_path'] ?? null);
	}

	/*
	 *
	 */

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overrideId($id = null) {
		if ($id && !$this->id) {
			$this->id = $id;
		}
	}

	/*
	 *
	 */

	public function init($id = null) {
		$id = $this->id ?? $id;

		if ($id) {
			if ($this->screen == 'dashboard') {
				add_action('wp_dashboard_setup', function($metabox_id) use ($id) {
					add_meta_box(
						$id,
						$this->title,
						function($post, $meta_box) use ($id) {
							$requestPath = ltrim($this->request->getRequestUri(), '/\\');
							return $this->autoResolveAndCall(
								$id,
								$this->extraParams['full_path'],
								$requestPath,
								$this,
								$this->callback_function,
								[
									'post'     => $post,
									'meta_box' => $meta_box,
								]
							);
						},
						$this->screen,
						$this->context,
						$this->priority,
						array_merge($this->callback_args ?? [], ['id' => $id, 'metabox_id' => $metabox_id])
					);
				}, $this->extraParams['priority'] ?? 10, $this->extraParams['accepted_args'] ?? 1);
			}
			else {
				add_action('add_meta_boxes', function($metabox_id) use ($id) {
					add_meta_box(
						$id,
						$this->title,
						function($post, $meta_box) use ($metabox_id, $id) {
							$requestPath = ltrim($this->request->getRequestUri(), '/\\');
							return $this->autoResolveAndCall(
								$id,
								$this->extraParams['full_path'],
								$requestPath,
								$this,
								$this->callback_function,
								[
									'post'     => $post,
									'meta_box' => $meta_box,
								]
							);
						},
						$this->screen,
						$this->context,
						$this->priority,
						array_merge($this->callback_args ?? [], ['id' => $id, 'metabox_id' => $metabox_id])
					);
				}, $this->extraParams['priority'] ?? 10, $this->extraParams['accepted_args'] ?? 1);
			}
		}
	}

}