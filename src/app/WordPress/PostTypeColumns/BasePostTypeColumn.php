<?php

namespace WPSPCORE\App\WordPress\PostTypeColumns;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

/**
 * @method void sort($query)
 */
abstract class BasePostTypeColumn extends BaseInstances {

	use ObjectToArrayTrait;

	public 	$column_name             = null;
	public 	$column_title            = null;
	public 	$column_add_priority     = 10;
	public 	$column_content_priority = 0;
	public 	$post_types              = ['post'];
	public 	$before_column           = [];
	public 	$after_column            = [];
	public 	$position                = null;
	public 	$sortable                = false;

	public 	$callback_function       = null;

	private $path 					 = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideColumnName($this->extraParams['full_path'] ?? null);
		$this->path = $this->extraParams['path'] ?? null;
	}

	/*
	 *
	 */

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overrideColumnName($column_name = null) {
		if ($column_name && !$this->column_name) {
			$this->column_name = $column_name;
		}
	}

	/*
	 *
	 */

	public function init($column_name = null) {
		$requestPath = ltrim($this->request->getRequestUri(), '/\\');
		$column_name = $this->column_name ?? $column_name;

		if ($column_name) {
			foreach ($this->post_types as $post_type) {

				/**
				 * Add column to each post type list table.
				 */
				add_filter('manage_' . $post_type . '_posts_columns', function($columns) use ($column_name) {
					$new_columns = [];
					$inserted = false;

					// Kiểm tra nếu có before_column
					if (!empty($this->before_column)) {
						$before_columns = is_array($this->before_column) ? $this->before_column : [$this->before_column];

						foreach ($columns as $key => $value) {
							if (in_array($key, $before_columns)) {
								$new_columns[$column_name] = $this->column_title ?? $column_name;
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
								$new_columns[$column_name] = $this->column_title ?? $column_name;
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
								$new_columns[$column_name] = $this->column_title ?? $column_name;
								$inserted = true;
							}
							$new_columns[$key] = $value;
							$i++;
						}

						// Nếu position lớn hơn số lượng columns hiện tại
						if (!$inserted) {
							$new_columns[$column_name] = $this->column_title ?? $column_name;
							$inserted = true;
						}
					}

					// Nếu chưa insert được (trường hợp không tìm thấy before/after/position)
					if (!$inserted) {
						$new_columns = $columns;
						$new_columns[$column_name] = $this->column_title ?? $column_name;
					}

					return $new_columns;
				}, $this->column_add_priority);

				/**
				 * The column content.
				 */
				add_action('manage_' . $post_type . '_posts_custom_column', function($columnName, $postId) use ($requestPath, $column_name) {
					if ($columnName === $column_name) {
						$this->autoResolveAndCall(
							$this->path,
							$column_name,
							$requestPath,
							$this,
							$this->callback_function,
							[
								'column_name' => $column_name,
								'post_id'     => $postId,
							]
						);
					}
				}, $this->column_content_priority, 2);

				/**
				 * Sortable column.
				 */
				if ($this->sortable) {
					add_filter('manage_edit-' . $post_type . '_sortable_columns', function($columns) {
						$columns[$this->column_name] = $this->column_name;
						return $columns;
					});
				}
			}

			if (method_exists($this, 'sort')) {
				add_action('pre_get_posts', [$this, 'sort'], 9999999999);
			}

			$this->afterInit();
		}
	}

	/*
	 *
	 */

	public function afterInit() {}

}