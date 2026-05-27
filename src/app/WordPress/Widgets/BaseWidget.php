<?php

namespace WPSPCORE\App\WordPress\Widgets;

use WPSPCORE\App\Traits\BaseInstancesTrait;

abstract class BaseWidget extends \WP_Widget {

	use BaseInstancesTrait;

	public $id_base         = null;
	public $name            = null;
	public $widget_options  = [];
	public $control_options = [];

	/*
	 *
	 */

	public function __construct() {
		// Khởi tạo các thuộc tính cơ bản.
		$this->beforeInstanceConstruct();

		// Tùy chỉnh các tham số.
		$this->customProperties();

		// Cần gọi __construct của parent trước.
		parent::__construct(
			$this->id_base,
			$this->name,
			$this->widget_options,
			$this->control_options
		);
	}

	/*
	 *
	 */

	public function customProperties() {}

	/*
	 *
	 */

	public function init() {
//		add_action('widgets_init', function() {
			register_widget(static::class);
//		});
	}

}