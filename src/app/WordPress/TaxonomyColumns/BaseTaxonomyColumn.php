<?php

namespace WPSPCORE\App\WordPress\TaxonomyColumns;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

/**
 * @method void sort($query)
 */
abstract class BaseTaxonomyColumn extends BaseInstances {

	use ObjectToArrayTrait;

	public $column                  = null;
	public $column_title            = null;
	public $column_add_priority     = 10;
	public $column_content_priority = 0;
	public $taxonomies              = ['category'];
	public $before_column           = [];
	public $after_column            = [];
	public $position                = null;
	public $sortable                = false;
	public $callback_function       = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'] ?? null;
		$this->overrideColumn($this->extraParams['full_path'] ?? null);
	}

	/*
	 *
	 */

	protected function overrideColumn($column = null) {
		if ($column && !$this->column) {
			$this->column = $column;
		}
	}

	/*
	 *
	 */

	public function init($column = null) {
		$column = $this->column ?? $column;
		if ($column) {
			foreach ($this->taxonomies as $taxonomy) {

				/**
				 * Add column to each post type list table.
				 */
				add_filter('manage_edit-'.$taxonomy.'_columns', function($columns) use ($column) {
					$new_columns = [];
					$inserted = false;

					// Kiểm tra nếu có before_column
					if (!empty($this->before_column)) {
						$before_columns = is_array($this->before_column) ? $this->before_column : [$this->before_column];

						foreach ($columns as $key => $value) {
							if (in_array($key, $before_columns)) {
								$new_columns[$column] = $this->column_title ?? $column;
								$inserted = true;
							}
							$new_columns[$key] = $value;
						}
					}

					// Nếu không có before_column, kiểm tra after_column
					elseif (!empty($this->after_column)) {
						$after_columns = is_array($this->after_column) ? $this->after_column : [$this->after_column];

						foreach ($columns as $key => $value) {
							$new_columns[$key] = $value;
							if (in_array($key, $after_columns)) {
								$new_columns[$column] = $this->column_title ?? $column;
								$inserted = true;
							}
						}
					}

					// Nếu không có after_column, sử dụng position
					elseif ($this->position) {
						$position = (int)$this->position;
						$i = 0;

						foreach ($columns as $key => $value) {
							if ($i === $position) {
								$new_columns[$column] = $this->column_title ?? $column;
								$inserted = true;
							}
							$new_columns[$key] = $value;
							$i++;
						}

						// Nếu position lớn hơn số lượng columns hiện tại
						if (!$inserted) {
							$new_columns[$column] = $this->column_title ?? $column;
							$inserted = true;
						}
					}

					// Nếu chưa insert được (trường hợp không tìm thấy before/after/position)
					if (!$inserted) {
						$new_columns = $columns;
						$new_columns[$column] = $this->column_title ?? $column;
					}

					return $new_columns;
				}, $this->column_add_priority);

				/**
				 * The column content.
				 */
				add_filter('manage_' . $taxonomy . '_custom_column', function($content, $columnName, $termId) use ($column) {
					if ($columnName === $column) {
						return call_user_func_array([$this, $this->callback_function], func_get_args());
					}
					return $content;
				}, $this->column_content_priority, 3);

				/**
				 * Sortable column.
				 */
				if ($this->sortable) {
					add_filter('manage_edit-' . $taxonomy . '_sortable_columns', function($columns) {
						$columns[$this->column] = $this->column;
						return $columns;
					});
				}
			}

			if (method_exists($this, 'sort')) {
				add_action('pre_get_terms', [$this, 'sort'], 9999);
			}

			$this->afterInit();
		}
	}

	/*
	 *
	 */

	public function afterInit() {}

	/*
	 *
	 */

	abstract public function index($content, $columnName, $termId);

}