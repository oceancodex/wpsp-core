<?php

namespace WPSPCORE\Components\PostTypes;

use WPSPCORE\Traits\ObjectToArrayTrait;

class PostTypeData {

	use ObjectToArrayTrait;

	public $post_type;

	// Labels.
	public $name;
	public $singular_name;
	public $add_new;
	public $add_new_item;
	public $edit_item;
	public $new_item;
	public $view_item;
	public $view_items;
	public $search_items;
	public $not_found;
	public $not_found_in_trash;
	public $parent_item_colon;
	public $all_items;
	public $archives;
	public $attributes;
	public $insert_into_item;
	public $uploaded_to_this_item;
	public $featured_image;
	public $set_featured_image;
	public $remove_featured_image;
	public $use_featured_image;
	public $menu_name;
	public $filter_items_list;
	public $filter_by_date;
	public $items_list_navigation;
	public $items_list;
	public $item_published;
	public $item_published_privately;
	public $item_reverted_to_draft;
	public $item_trashed;
	public $item_scheduled;
	public $item_updated;
	public $item_link;
	public $item_link_description;

	// Args.
	public $label;
	public $labels;
	public $description;
	public $public;
	public $hierarchical;
	public $exclude_from_search;
	public $publicly_queryable;
	public $show_ui;
	public $show_in_menu;
	public $show_in_nav_menus;
	public $show_in_admin_bar;
	public $show_in_rest;
	public $rest_base;
	public $rest_namespace;
	public $rest_controller_class;
	public $autosave_rest_controller_class;
	public $revisions_rest_controller_class;
	public $late_route_registration;
	public $menu_position;
	public $menu_icon;
	public $capability_type;
	public $capabilities;
	public $map_meta_cap;
	public $supports;
	public $register_meta_box_cb;
	public $taxonomies;
	public $has_archive;
	public $rewrite;
//	public $slug;
//	public $with_front;
//	public $feeds;
//	public $pages;
//	public $ep_mask;
	public $can_export;
	public $delete_with_user;
	public $template;
	public $template_lock;
	public $_builtin;
//	public $_edit_link;                           // Warning: This attribute may affect post editing.
//	public $query_var;                            // Warning: This attribute can affect article viewing beyond the frontend.

	// Custom properties.
	public $preparedName = null;
	public $postTypeInstance;
	public $previousArgs;

	public function __construct($postTypeInstance = null, $previousArgs = null) {
		$this->postTypeInstance = $postTypeInstance;
		$this->previousArgs     = $previousArgs;
		$this->prepareCustomVariables();
		$this->prepareArgs();
		$this->prepareLabels();
	}

	public function prepareArgs() {
		$this->label                           = null;
		$this->labels                          = [];
		$this->description                     = '';
		$this->public                          = true;
		$this->hierarchical                    = true;
		$this->exclude_from_search             = false;
		$this->publicly_queryable              = true;
		$this->show_ui                         = true;
		$this->show_in_menu                    = true;
		$this->show_in_nav_menus               = true;
		$this->show_in_admin_bar               = true;
		$this->show_in_rest                    = true;
//		$this->rest_base                       = '';
//		$this->rest_namespace                  = '';
//		$this->rest_controller_class           = '';
//		$this->autosave_rest_controller_class  = '';
//		$this->revisions_rest_controller_class = '';
//		$this->late_route_registration         = true;
		$this->menu_position                   = null;
		$this->menu_icon                       = null;
		$this->capability_type                 = 'post';
		$this->capabilities                    = [];
//		$this->map_meta_cap                    = false;
		$this->supports                        = ['title', 'editor'];
		$this->register_meta_box_cb            = null;
		$this->taxonomies                      = [];
		$this->has_archive                     = false;
		$this->rewrite                         = true;  // false, ['slug' => $this->post_type ?? null, 'with_front' => true, 'feeds' => true, 'pages' => true, 'ep_mask' => 0];
//		$this->query_var                       = false;
		$this->can_export                      = true;  // true, false
		$this->delete_with_user                = false; // true, false, null
//		$this->template                        = [];
		$this->template_lock                   = false; // 'all', 'insert', false
		$this->_builtin                        = false;
//		$this->_edit_link                      = '';
	}

	public function prepareLabels() {
		$this->labels['name']                     = $this->previousArgs->labels['name'] ?? $this->preparedName;
		$this->labels['singular_name']            = $this->previousArgs->labels['singular_name'] ?? $this->preparedName;
		$this->labels['add_new']                  = $this->previousArgs->labels['add_new'] ?? 'Add new ' . $this->preparedName;
		$this->labels['add_new_item']             = $this->previousArgs->labels['add_new_item'] ?? 'Add new ' . $this->preparedName;
		$this->labels['edit_item']                = $this->previousArgs->labels['edit_item'] ?? 'Edit ' . $this->preparedName;
		$this->labels['new_item']                 = $this->previousArgs->labels['new_item'] ?? 'New ' . $this->preparedName;
		$this->labels['view_item']                = $this->previousArgs->labels['view_item'] ?? 'View ' . $this->preparedName;
		$this->labels['view_items']               = $this->previousArgs->labels['view_items'] ?? 'View ' . $this->preparedName;
		$this->labels['search_items']             = $this->previousArgs->labels['search_items'] ?? 'Search ' . $this->preparedName;
		$this->labels['not_found']                = $this->previousArgs->labels['not_found'] ?? 'No ' . $this->preparedName . ' found';
		$this->labels['not_found_in_trash']       = $this->previousArgs->labels['not_found_in_trash'] ?? 'No ' . $this->preparedName . ' found in Trash';
		$this->labels['parent_item_colon']        = $this->previousArgs->labels['parent_item_colon'] ?? 'Parent ' . $this->preparedName . ':';
		$this->labels['all_items']                = $this->previousArgs->labels['all_items'] ?? 'All ' . $this->preparedName;
		$this->labels['archives']                 = $this->previousArgs->labels['archives'] ?? 'Archives for ' . $this->preparedName;
		$this->labels['attributes']               = $this->previousArgs->labels['attributes'] ?? 'Attributes for ' . $this->preparedName;
		$this->labels['insert_into_item']         = $this->previousArgs->labels['insert_into_item'] ?? 'Insert into' . ' ' . $this->preparedName;
		$this->labels['uploaded_to_this_item']    = $this->previousArgs->labels['uploaded_to_this_item'] ?? 'Uploaded to this ' . $this->preparedName;
		$this->labels['featured_image']           = $this->previousArgs->labels['featured_image'] ?? 'Featured image for ' . $this->preparedName;
		$this->labels['set_featured_image']       = $this->previousArgs->labels['set_featured_image'] ?? 'Set featured image for ' . $this->preparedName;
		$this->labels['remove_featured_image']    = $this->previousArgs->labels['remove_featured_image'] ?? 'Remove featured image for ' . $this->preparedName;
		$this->labels['use_featured_image']       = $this->previousArgs->labels['use_featured_image'] ?? 'Use as featured image for ' . $this->preparedName;
		$this->labels['menu_name']                = $this->previousArgs->labels['menu_name'] ?? $this->preparedName;
		$this->labels['filter_items_list']        = $this->previousArgs->labels['filter_items_list'] ?? 'Filter ' . $this->preparedName;
		$this->labels['filter_by_date']           = $this->previousArgs->labels['filter_by_date'] ?? 'Filter by date';
		$this->labels['items_list_navigation']    = $this->previousArgs->labels['items_list_navigation'] ?? 'Items for ' . $this->preparedName;
		$this->labels['items_list']               = $this->previousArgs->labels['items_list'] ?? 'Items for ' . $this->preparedName;
		$this->labels['item_published']           = $this->previousArgs->labels['item_published'] ?? $this->preparedName . ' published';
		$this->labels['item_published_privately'] = $this->previousArgs->labels['item_published_privately'] ?? $this->preparedName . ' published privately';
		$this->labels['item_reverted_to_draft']   = $this->previousArgs->labels['item_reverted_to_draft'] ?? $this->preparedName . ' reverted to draft';
		$this->labels['item_trashed']             = $this->previousArgs->labels['item_trashed'] ?? $this->preparedName . ' trashed';
		$this->labels['item_scheduled']           = $this->previousArgs->labels['item_scheduled'] ?? $this->preparedName . ' scheduled';
		$this->labels['item_updated']             = $this->previousArgs->labels['item_updated'] ?? $this->preparedName . ' updated';
		$this->labels['item_link']                = $this->previousArgs->labels['item_link'] ?? $this->preparedName . ' link';
		$this->labels['item_link_description']    = $this->previousArgs->labels['item_link_description'] ?? $this->preparedName . ' link description';
		unset($this->preparedName);
		foreach ($this->labels as $key => $label) {
			unset($this->{$key});
		}
	}

	public function prepareCustomVariables() {
		if ($this->previousArgs) {
			$this->preparedName = $this->previousArgs->labels['name']
				?? $this->previousArgs->labels['singular_name']
				?? $this->postTypeInstance->args->labels['name']
				?? $this->postTypeInstance->args->labels['singular_name']
				?? $this->postTypeInstance->post_type
				?? $this->name
				?? $this->singular_name
				?? $this->post_type
				?? null;
		}
		else {
			$this->preparedName = $this->postTypeInstance->name
				?? $this->postTypeInstance->singular_name
				?? $this->postTypeInstance->post_type
				?? $this->name
				?? $this->singular_name
				?? $this->post_type
				?? null;
		}
		unset($this->postTypeInstance);
	}

}