<?php

namespace WPSPCORE\WP\PostTypeColumns;

use WPSPCORE\BaseInstances;
use WPSPCORE\Traits\ObjectToArrayTrait;

/**
 * @method void sort($query)
 */
abstract class BasePostTypeColumn extends BaseInstances {

	use ObjectToArrayTrait;

	public $column                  = null;
	public $column_add_priority     = 10;
	public $column_content_priority = 0;
	public $post_types              = ['post'];
	public $before_column           = [];
	public $after_column            = ['title'];
	public $position                = 2;
	public $sortable                = false;
	public $callback_function       = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'] ?? null;
		$this->overrideColumn($this->extraParams['column'] ?? null);
		$this->customProperties();
	}

	/*
	 *
	 */

	public function init($column = null) {
		$column = $this->column ?? $column;
		if ($column) {
			foreach ($this->post_types as $post_type) {

				/**
				 * Add column to each post type list table.
				 */
				add_filter('manage_' . $post_type . '_posts_columns', function($columns) use ($column) {
					$new_columns = [];
					$inserted = false;

					// Kiểm tra nếu có before_column
					if ($this->before_column) {
						$before_columns = is_array($this->before_column) ? $this->before_column : [$this->before_column];

						foreach ($columns as $key => $value) {
							if (in_array($key, $before_columns)) {
								$new_columns[$column] = $column;
								$inserted = true;
							}
							$new_columns[$key] = $value;
						}
					}
					// Nếu không có before_column, kiểm tra after_column
					elseif ($this->after_column) {
						$after_columns = is_array($this->after_column) ? $this->after_column : [$this->after_column];

						foreach ($columns as $key => $value) {
							$new_columns[$key] = $value;
							if (in_array($key, $after_columns)) {
								$new_columns[$column] = $column;
								$inserted = true;
							}
						}
					}
					// Nếu không có after_column, sử dụng position
					elseif ($this->position !== null) {
						$position = (int) $this->position;
						$i = 0;

						foreach ($columns as $key => $value) {
							if ($i === $position) {
								$new_columns[$column] = $column;
								$inserted = true;
							}
							$new_columns[$key] = $value;
							$i++;
						}

						// Nếu position lớn hơn số lượng columns hiện tại
						if (!$inserted) {
							$new_columns[$column] = $column;
							$inserted = true;
						}
					}

					// Nếu chưa insert được (trường hợp không tìm thấy before/after column)
					if (!$inserted) {
						$new_columns[$column] = $column;
					}

					return $new_columns;
				}, $this->column_add_priority);

				/**
				 * The column content.
				 */
				add_action('manage_' . $post_type . '_posts_custom_column', [$this, $this->callback_function], $this->column_content_priority, 2);

				/**
				 * Sortable column.
				 */
				if ($this->sortable) {
					add_filter('manage_edit-' . $post_type . '_sortable_columns', function($columns) {
						$columns[$this->column] = $this->column;
						return $columns;
					});
				}
			}

			if (method_exists($this, 'sort')) {
				add_action('pre_get_posts', [$this, 'sort'], 9999);
			}

			$this->afterInit();
		}
	}

	/*
	 *
	 */

	private function overrideColumn($column = null) {
		if ($column && !$this->column) {
			$this->column = $column;
		}
	}

	/*
	 *
	 */

	abstract public function customProperties();

	abstract public function index($column, $postId);

	abstract public function afterInit();

}