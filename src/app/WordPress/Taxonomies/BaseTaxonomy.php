<?php

namespace WPSPCORE\App\WordPress\Taxonomies;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

/**
 * @property $labels
 */
abstract class BaseTaxonomy extends BaseInstances {

	use ObjectToArrayTrait;

	public $taxonomy				   = null;
	public $object_type				   = 'post';     // The post type which the taxonomy will be associated with.

	public $args					   = null;

	/** Labels. */
	public $name                       = null;
	public $singular_name              = null;
	public $search_items               = null;
	public $popular_items              = null;
	public $all_items                  = null;
	public $parent_item                = null;
	public $parent_item_colon          = null;
	public $name_field_description     = null;
	public $slug_field_description     = null;
	public $parent_field_description   = null;
	public $desc_field_description     = null;
	public $edit_item                  = null;
	public $view_item                  = null;
	public $update_item                = null;
	public $add_new_item               = 'Add new';
	public $new_item_name              = null;
	public $separate_items_with_commas = null;
	public $add_or_remove_items        = null;
	public $choose_from_most_used      = null;
	public $not_found                  = null;
	public $no_terms                   = null;
	public $filter_by_item             = null;
	public $items_list_navigation      = null;
	public $items_list                 = null;
	public $most_used                  = null;
	public $back_to_items              = null;
	public $item_link                  = null;
	public $item_link_description      = null;

	/** Arguments. */
//	public $labels                     = [];		// Phải comment dòng này, nếu không toàn bộ labels sẽ mất, labels sẽ được tạo ra từ class con.
	public $description                = null;
	public $public                     = true;
	public $publicly_queryable         = true;
	public $hierarchical               = false;
	public $show_ui                    = true;
	public $show_in_menu               = true;
	public $show_in_nav_menus          = true;
	public $show_in_rest               = true;
	public $rest_base                  = null;
	public $rest_namespace             = null;
	public $rest_controller_class      = null;
	public $show_tagcloud              = true;
	public $show_in_quick_edit         = true;
	public $show_admin_column          = true;
	public $meta_box_cb                = null;
	public $meta_box_sanitize_cb       = null;
	public $capabilities               = [];		// ['manage_terms' => 'manage_categories', 'edit_terms' => 'manage_categories', 'delete_terms' => 'manage_categories', 'assign_terms' => 'edit_posts']
	public $rewrite                    = [];		// true/false or ['slug', 'with_front', 'hierarchical', 'ep_mask']
	public $update_count_callback      = null;
	public $default_term               = [];		// ['name','slug', 'description']
	public $sort                       = false;

	public $query_var                  = false;		// Not for general use. Warning: This attribute can affect article viewing beyond the frontend.
	public $_builtin                   = false;		// Not for general use

	public $callback_function		   = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideTaxonomy($this->extraParams['full_path']);

		// Init args.
		$this->args = new TaxonomyData($this);

		$this->prepareArguments();
	}

	/**
	 * Ở class base con, sau khi custom properties thì cần chạy prepareArguments()\
	 * trong hàm afterBaseInstanceConstruct() vì hàm này chạy sau customProperties().
	 */
	public function afterBaseInstanceConstruct() {
		$this->prepareArguments();
	}

	/*
	 *
	 */

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overrideTaxonomy($taxonomy = null) {
		if ($taxonomy && !$this->taxonomy) {
			$this->taxonomy = $taxonomy;
		}
	}

	/*
	 *
	 */

	public function init($taxonomy = null) {
		$taxonomy = $this->taxonomy ?? $taxonomy;
		if ($taxonomy) {
//			add_action('init', function() use ($taxonomy) {
				register_taxonomy($taxonomy, $this->object_type, $this->args);
//			});
		}
	}

	/*
	 *
	 */

	private function prepareArguments() {
		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
			}
			if (array_key_exists($key, $this->args->labels)) {
				$this->args->labels[$key] = $value;
			}
		}
	}

}