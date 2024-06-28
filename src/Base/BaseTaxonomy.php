<?php

namespace WPSPCORE\Base;

use WPSPCORE\Data\TaxonomyData;
use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

/**
 * // Labels.
 * @property $name;
 * @property $singular_name;
 * @property $search_items;
 * @property $popular_items;
 * @property $all_items;
 * @property $parent_item;
 * @property $parent_item_colon;
 * @property $name_field_description;
 * @property $slug_field_description;
 * @property $parent_field_description;
 * @property $desc_field_description;
 * @property $edit_item;
 * @property $view_item;
 * @property $update_item;
 * @property $add_new_item;
 * @property $new_item_name;
 * @property $separate_items_with_commas;
 * @property $add_or_remove_items;
 * @property $choose_from_most_used;
 * @property $not_found;
 * @property $no_terms;
 * @property $filter_by_item;
 * @property $items_list_navigation;
 * @property $items_list;
 * @property $most_used;
 * @property $back_to_items;
 * @property $item_link;
 * @property $item_link_description;
 *
 * // Args.
 * @property $labels;
 * @property $description;
 * @property $public;
 * @property $publicly_queryable;
 * @property $hierarchical;
 * @property $show_ui;
 * @property $show_in_menu;
 * @property $show_in_nav_menus;
 * @property $show_in_rest;
 * @property $rest_base;
 * @property $rest_namespace;
 * @property $rest_controller_class;
 * @property $show_tagcloud;
 * @property $show_in_quick_edit;
 * @property $show_admin_column;
 * @property $meta_box_cb;
 * @property $meta_box_sanitize_cb;
 * @property $capabilities;
 * @property $rewrite;
 * @property $query_var;
 * @property $update_count_callback;
 * @property $default_term;
 * @property $sort;
 * @property $args;
 * @property $_builtin;
 */
abstract class BaseTaxonomy extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public mixed $taxonomy    = null;
	public mixed $object_type = 'post';     // The post type which the taxonomy will be associated with.
	public mixed $args        = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $taxonomy = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->overrideTaxonomy($taxonomy);
		$this->prepareArguments();
		$this->customProperties();
		$this->maybePrepareArgumentsAgain($taxonomy);
	}

	/*
	 *
	 */

	public function overrideTaxonomy($taxonomy = null): void {
		if ($taxonomy && !$this->taxonomy) {
			$this->taxonomy = $taxonomy;
		}
	}

	public function prepareArguments(): void {
		$this->args = new TaxonomyData($this);
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

	public function maybePrepareArgumentsAgain($taxonomy = null): void {
		if ($taxonomy !== $this->taxonomy) {
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

	public function init(): void {
		if ($this->taxonomy) {
			register_taxonomy($this->taxonomy, $this->object_type, $this->args);
		}
	}

}