<?php

namespace WPSPCORE\Base;

use WPSPCORE\Data\PostTypeData;
use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

/**
 * @property $label
 * @property $labels
 * @property $description
 * @property $public
 * @property $hierarchical
 * @property $exclude_from_search
 * @property $publicly_queryable
 * @property $show_ui
 * @property $show_in_menu
 * @property $show_in_nav_menus
 * @property $show_in_admin_bar
 * @property $show_in_rest
 * @property $rest_base
 * @property $rest_namespace
 * @property $rest_controller_class
 * @property $autosave_rest_controller_class
 * @property $revisions_rest_controller_class
 * @property $late_route_registration
 * @property $menu_position
 * @property $menu_icon
 * @property $capability_type
 * @property $capabilities
 * @property $map_meta_cap
 * @property $supports
 * @property $register_meta_box_cb
 * @property $taxonomies
 * @property $has_archive
 * @property $rewrite
 * @property $slug
 * @property $with_front
 * @property $feeds
 * @property $pages
 * @property $ep_mask
 * @property $can_export
 * @property $delete_with_user
 * @property $template
 * @property $template_lock
 * @property $_builtin
 *
 * @property $_edit_link                            Warning: This attribute may affect post editing.
 * @property $query_var                             Warning: This attribute can affect article viewing beyond the frontend.
 */
abstract class BasePostType extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public ?string $post_type = null;
	public mixed   $args;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $postType = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->overridePostType($postType);
		$this->prepareArguments();
		$this->customProperties();
		$this->maybePrepareArgumentsAgain($postType);
	}

	/*
	 *
	 */

	public function overridePostType($postType = null): void {
		if ($postType && !$this->post_type) {
			$this->post_type = $postType;
		}
	}

	public function prepareArguments(): void {
		$this->args = new PostTypeData($this);
		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
				unset($this->args->{$key});
			}
			if (array_key_exists($key, $this->args->labels)) {
				$this->args->labels[$key] = $value;
				unset($this->args->{$key});
			}
		}
	}

	public function maybePrepareArgumentsAgain($postType = null): void {
		if ($postType !== $this->post_type) {
			$this->prepareArguments();
		}
	}

	/*
	 *
	 */

	abstract public function customProperties();

	/*
	 *
	 */

	public function init($postType = null): void {
		register_post_type($this->post_type, $this->args);
	}

}