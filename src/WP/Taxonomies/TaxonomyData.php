<?php

namespace WPSPCORE\WP\Taxonomies;

use WPSPCORE\Traits\ObjectToArrayTrait;

class TaxonomyData {

	use ObjectToArrayTrait;

	public $taxonomy;
	public $object_type;

	// Labels.
	public $name;
	public $singular_name;
	public $search_items;
	public $popular_items;
	public $all_items;
	public $parent_item;
	public $parent_item_colon;
	public $name_field_description;
	public $slug_field_description;
	public $parent_field_description;
	public $desc_field_description;
	public $edit_item;
	public $view_item;
	public $update_item;
	public $add_new_item;
	public $new_item_name;
	public $separate_items_with_commas;
	public $add_or_remove_items;
	public $choose_from_most_used;
	public $not_found;
	public $no_terms;
	public $filter_by_item;
	public $items_list_navigation;
	public $items_list;
	public $most_used;
	public $back_to_items;
	public $item_link;
	public $item_link_description;

	// Args.
	public $labels;
	public $description;
	public $public;
	public $publicly_queryable;
	public $hierarchical;
	public $show_ui;
	public $show_in_menu;
	public $show_in_nav_menus;
	public $show_in_rest;
	public $rest_base;
	public $rest_namespace;
	public $rest_controller_class;
	public $show_tagcloud;
	public $show_in_quick_edit;
	public $show_admin_column;
	public $meta_box_cb;
	public $meta_box_sanitize_cb;
	public $capabilities;                 // manage_terms, edit_terms, delete_terms, assign_terms
	public $rewrite;                      // slug, with_front, hierarchical, ep_mask
	public $query_var;
	public $update_count_callback;
	public $default_term;                 // name, slug, description
	public $sort;
	public $args;
	public $_builtin;

	// Custom properties.
	public $preparedName;
	public $taxonomyInstance;
	public $previousArgs;

	public function __construct($taxonomyInstance = null, $previousArgs = null) {
		$this->taxonomyInstance = $taxonomyInstance;
		$this->previousArgs     = $previousArgs;
		$this->prepareCustomVariables();
		$this->prepareArgs();
		$this->prepareLabels();
	}

	public function prepareArgs() {
		$this->labels                = [];
		$this->description           = '';
		$this->public                = true;
		$this->publicly_queryable    = true;
		$this->hierarchical          = false;
		$this->show_ui               = true;
		$this->show_in_menu          = true;
		$this->show_in_nav_menus     = true;
		$this->show_in_rest          = true;
//		$this->rest_base             = '';
//		$this->rest_namespace        = '';
//		$this->rest_controller_class = '';
		$this->show_tagcloud         = true;
		$this->show_in_quick_edit    = true;
		$this->show_admin_column     = true;
//		$this->meta_box_cb           = null;
//		$this->meta_box_sanitize_cb  = null;
		$this->capabilities          = [];          // ['manage_terms', 'edit_terms', 'delete_terms', 'assign_terms']
//		$this->rewrite               = [];          // true/false or ['slug', 'with_front', 'hierarchical', 'ep_mask']
//		$this->query_var             = '';
//		$this->update_count_callback = null;
//		$this->default_term          = [];          // ['name','slug', 'description']
//		$this->sort                  = null;
//		$this->args                  = [];
//		$this->_builtin              = true;
	}

	public function prepareLabels() {
		$this->labels['name']                       = $this->preparedName;
		$this->labels['singular_name']              = $this->preparedName;
		$this->labels['search_items']               = 'Search ' . $this->preparedName;
		$this->labels['popular_items']              = 'Popular ' . $this->preparedName;
		$this->labels['all_items']                  = 'All ' . $this->preparedName;
		$this->labels['parent_item']                = 'Parent ' . $this->preparedName;
		$this->labels['parent_item_colon']          = 'Parent ' . $this->preparedName . ':';
		$this->labels['name_field_description']     = 'The name is how it appears on your site';
		$this->labels['slug_field_description']     = 'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens';
		$this->labels['parent_field_description']   = 'Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band';
		$this->labels['desc_field_description']     = 'The description is not prominent by default; however, some themes may show it';
		$this->labels['edit_item']                  = 'Edit ' . $this->preparedName;
		$this->labels['view_item']                  = 'View ' . $this->preparedName;
		$this->labels['update_item']                = 'Update ' . $this->preparedName;
		$this->labels['add_new_item']               = 'Add new ' . $this->preparedName;
		$this->labels['new_item_name']              = 'New ' . $this->preparedName . ' name';
		$this->labels['separate_items_with_commas'] = 'Separate ' . $this->preparedName . ' with commas';
		$this->labels['add_or_remove_items']        = 'Add or remove ' . $this->preparedName;
		$this->labels['choose_from_most_used']      = 'Choose from the most used ' . $this->preparedName;
		$this->labels['not_found']                  = 'No ' . $this->preparedName . ' found';
		$this->labels['no_terms']                   = 'No ' . $this->preparedName;
		$this->labels['filter_by_item']             = 'Filter by ' . $this->preparedName;
		$this->labels['items_list_navigation']      = '';
		$this->labels['items_list']                 = '';
		$this->labels['most_used']                  = 'Most ' . $this->preparedName . ' used';
		$this->labels['back_to_items']              = 'Back to ' . $this->preparedName;
		$this->labels['item_link']                  = $this->preparedName . ' link';
		$this->labels['item_link_description']      = 'A link to a ' . $this->preparedName;
		unset($this->preparedName);
		foreach ($this->labels as $key => $label) {
			unset($this->{$key});
		}
	}

	public function prepareCustomVariables() {
		$this->preparedName = $this->previousArgs->labels['name']
			?? $this->previousArgs->labels['singular_name']
			?? $this->taxonomyInstance->args->labels['name']
			?? $this->taxonomyInstance->args->labels['singular_name']
			?? $this->taxonomyInstance->taxonomy
			?? $this->name
			?? $this->singular_name
			?? $this->taxonomy;
		unset($this->taxonomyInstance);
		unset($this->previousArgs);
	}

}