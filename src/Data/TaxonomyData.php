<?php

namespace WPSPCORE\Data;

use WPSPCORE\Base\BaseData;
use WPSPCORE\Base\BaseTaxonomy;

class TaxonomyData extends BaseData {
	public mixed $taxonomy;
	public mixed $object_type;

	// Labels.
	public mixed $name;
	public mixed $singular_name;
	public mixed $search_items;
	public mixed $popular_items;
	public mixed $all_items;
	public mixed $parent_item;
	public mixed $parent_item_colon;
	public mixed $name_field_description;
	public mixed $slug_field_description;
	public mixed $parent_field_description;
	public mixed $desc_field_description;
	public mixed $edit_item;
	public mixed $view_item;
	public mixed $update_item;
	public mixed $add_new_item;
	public mixed $new_item_name;
	public mixed $separate_items_with_commas;
	public mixed $add_or_remove_items;
	public mixed $choose_from_most_used;
	public mixed $not_found;
	public mixed $no_terms;
	public mixed $filter_by_item;
	public mixed $items_list_navigation;
	public mixed $items_list;
	public mixed $most_used;
	public mixed $back_to_items;
	public mixed $item_link;
	public mixed $item_link_description;

	// Args.
	public mixed $labels;
	public mixed $description;
	public mixed $public;
	public mixed $publicly_queryable;
	public mixed $hierarchical;
	public mixed $show_ui;
	public mixed $show_in_menu;
	public mixed $show_in_nav_menus;
	public mixed $show_in_rest;
	public mixed $rest_base;
	public mixed $rest_namespace;
	public mixed $rest_controller_class;
	public mixed $show_tagcloud;
	public mixed $show_in_quick_edit;
	public mixed $show_admin_column;
	public mixed $meta_box_cb;
	public mixed $meta_box_sanitize_cb;
	public mixed $capabilities;                 // manage_terms, edit_terms, delete_terms, assign_terms
	public mixed $rewrite;                      // slug, with_front, hierarchical, ep_mask
	public mixed $query_var;
	public mixed $update_count_callback;
	public mixed $default_term;                 // name, slug, description
	public mixed $sort;
	public mixed $args;
	public mixed $_builtin;

	// Custom properties.
	public mixed $preparedName;
	public mixed $taxonomyInstance;
	public mixed $previousArgs;

	public function __construct(?BaseTaxonomy $taxonomyInstance = null, $previousArgs = null) {
		$this->taxonomyInstance = $taxonomyInstance;
		$this->previousArgs     = $previousArgs;
		$this->prepareCustomVariables();
		$this->prepareArgs();
		$this->prepareLabels();
	}

	public function prepareArgs(): void {
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

	public function prepareLabels(): void {
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

	public function prepareCustomVariables(): void {
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