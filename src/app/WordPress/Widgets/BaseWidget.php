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

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		// Khởi tạo các thuộc tính cơ bản.
		$this->baseInstanceConstruct($mainPath, $rootNamespace, $prefixEnv, $extraParams);

		// Cần gọi __construct của parent trước.
		parent::__construct(
			$this->id_base,
			$this->name ?? $this->id_base,
			$this->widget_options,
			$this->control_options
		);
	}

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideIdBase($this->extraParams['full_path']);
	}

	/*
	 *
	 */

	public function customProperties() {}

	/*
	 *
	 */

	protected function overrideIdBase($id_base = null) {
		if ($id_base && !$this->id_base) {
			$this->id_base = $id_base;
		}
	}

	/*
	 *
	 */

	public function init($id_base = null) {
		$id_base = $this->id_base ?? $id_base;

		if ($id_base) {
//		    add_action('widgets_init', function() {
				register_widget($this);
//		    });
		}
	}

}