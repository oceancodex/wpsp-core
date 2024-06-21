<?php

namespace WPSPCORE\Objects\Data;

use WPSPCORE\Base\BasePostType;
use WPSPCORE\Base\BaseData;

class PostTypeData extends BaseData {

	public mixed $post_type;

	// Labels.
	public mixed $name;
	public mixed $singular_name;
	public mixed $add_new;
	public mixed $add_new_item;
	public mixed $edit_item;
	public mixed $new_item;
	public mixed $view_item;
	public mixed $view_items;
	public mixed $search_items;
	public mixed $not_found;
	public mixed $not_found_in_trash;
	public mixed $parent_item_colon;
	public mixed $all_items;
	public mixed $archives;
	public mixed $attributes;
	public mixed $insert_into_item;
	public mixed $uploaded_to_this_item;
	public mixed $featured_image;
	public mixed $set_featured_image;
	public mixed $remove_featured_image;
	public mixed $use_featured_image;
	public mixed $menu_name;
	public mixed $filter_items_list;
	public mixed $filter_by_date;
	public mixed $items_list_navigation;
	public mixed $items_list;
	public mixed $item_published;
	public mixed $item_published_privately;
	public mixed $item_reverted_to_draft;
	public mixed $item_trashed;
	public mixed $item_scheduled;
	public mixed $item_updated;
	public mixed $item_link;
	public mixed $item_link_description;

	// Args.
	public mixed $label;
	public mixed $labels;
	public mixed $description;
	public mixed $public;
	public mixed $hierarchical;
	public mixed $exclude_from_search;
	public mixed $publicly_queryable;
	public mixed $show_ui;
	public mixed $show_in_menu;
	public mixed $show_in_nav_menus;
	public mixed $show_in_admin_bar;
	public mixed $show_in_rest;
	public mixed $rest_base;
	public mixed $rest_namespace;
	public mixed $rest_controller_class;
	public mixed $autosave_rest_controller_class;
	public mixed $revisions_rest_controller_class;
	public mixed $late_route_registration;
	public mixed $menu_position;
	public mixed $menu_icon;
	public mixed $capability_type;
	public mixed $capabilities;
	public mixed $map_meta_cap;
	public mixed $supports;
	public mixed $register_meta_box_cb;
	public mixed $taxonomies;
	public mixed $has_archive;
	public mixed $rewrite;
//	public mixed $slug;
//	public mixed $with_front;
//	public mixed $feeds;
//	public mixed $pages;
//	public mixed $ep_mask;
	public mixed $can_export;
	public mixed $delete_with_user;
	public mixed $template;
	public mixed $template_lock;
	public mixed $_builtin;
//	public mixed $_edit_link;
//	public mixed $query_var;

	// Custom properties.
	public mixed $preparedName;
	public mixed $postTypeInstance;

	public function __construct(BasePostType $postTypeInstance = null) {
		$this->postTypeInstance = $postTypeInstance;
		$this->prepareCustomVariables();
		$this->prepareArgs();
		$this->prepareLabels();
	}

	public function prepareArgs(): void {
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

	public function prepareLabels(): void {
		$this->labels['name']                     = $this->preparedName;
		$this->labels['singular_name']            = $this->preparedName;
		$this->labels['add_new']                  = 'Add new ' . $this->preparedName;
		$this->labels['add_new_item']             = 'Add new ' . $this->preparedName;
		$this->labels['edit_item']                = 'Edit ' . $this->preparedName;
		$this->labels['new_item']                 = 'New ' . $this->preparedName;
		$this->labels['view_item']                = 'View ' . $this->preparedName;
		$this->labels['view_items']               = 'View ' . $this->preparedName;
		$this->labels['search_items']             = 'Search ' . $this->preparedName;
		$this->labels['not_found']                = 'No ' . $this->preparedName . ' found';
		$this->labels['not_found_in_trash']       = 'No ' . $this->preparedName . ' found in Trash';
		$this->labels['parent_item_colon']        = 'Parent ' . $this->preparedName . ':';
		$this->labels['all_items']                = 'All ' . $this->preparedName;
		$this->labels['archives']                 = 'Archives for ' . $this->preparedName;
		$this->labels['attributes']               = 'Attributes for ' . $this->preparedName;
		$this->labels['insert_into_item']         = '\Insert into ' . $this->preparedName;
		$this->labels['uploaded_to_this_item']    = 'Uploaded to this ' . $this->preparedName;
		$this->labels['featured_image']           = 'Featured image for ' . $this->preparedName;
		$this->labels['set_featured_image']       = 'Set featured image for ' . $this->preparedName;
		$this->labels['remove_featured_image']    = 'Remove featured image for ' . $this->preparedName;
		$this->labels['use_featured_image']       = 'Use as featured image for ' . $this->preparedName;
		$this->labels['menu_name']                = $this->preparedName;
		$this->labels['filter_items_list']        = 'Filter ' . $this->preparedName;
		$this->labels['filter_by_date']           = 'Filter by date';
		$this->labels['items_list_navigation']    = 'Items for ' . $this->preparedName;
		$this->labels['items_list']               = 'Items for ' . $this->preparedName;
		$this->labels['item_published']           = $this->preparedName . ' published';
		$this->labels['item_published_privately'] = $this->preparedName . ' published privately';
		$this->labels['item_reverted_to_draft']   = $this->preparedName . ' reverted to draft';
		$this->labels['item_trashed']             = $this->preparedName . ' trashed';
		$this->labels['item_scheduled']           = $this->preparedName . ' scheduled';
		$this->labels['item_updated']             = $this->preparedName . ' updated';
		$this->labels['item_link']                = $this->preparedName . ' link';
		$this->labels['item_link_description']    = $this->preparedName . ' link description';
		unset($this->preparedName);
		foreach ($this->labels as $key => $label) {
			unset($this->{$key});
		}
	}

	public function prepareCustomVariables(): void {
		$this->preparedName = $this->singular_name ?? $this->name ?? $this->post_type ?? null;
		$this->preparedName = $this->preparedName ?: $this->postTypeInstance->singular_name ?? $this->postTypeInstance->name ?? $this->postTypeInstance->post_type;
		unset($this->postTypeInstance);
	}

}