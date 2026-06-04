<?php

namespace WPSPCORE\App\WordPress\DashboardWidgets;

use WPSPCORE\BaseInstances;

abstract class BaseDashboardWidget extends BaseInstances {

	public $widget_id         = null;
	public $widget_name       = null;
	public $callback_args     = null;
	public $context           = 'normal';
	public $priority          = 'core';
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideWidgetId($this->extraParams['full_path']);
	}

	/*
	 *
	 */

	protected function overrideWidgetId($widget_id = null) {
		if ($widget_id && !$this->widget_id) {
			$this->widget_id = $widget_id;
		}
	}

	/*
	 *
	 */

	public function init($widget_id = null) {
		$widget_id   = $this->widget_id ?? $widget_id;
		$requestPath = ltrim($this->request->getRequestUri(), '/\\');

		add_action('wp_dashboard_setup', function() use ($widget_id, $requestPath) {
			wp_add_dashboard_widget(
				$widget_id,
				$this->widget_name ?? $widget_id,
				function($post, $callback_args) use ($widget_id, $requestPath) {
					return $this->autoResolveAndCall($widget_id, $this->extraParams['full_path'], $requestPath, $this, $this->callback_function, ['post' => $post, 'callback_args' => $callback_args]);
				},
				function($post, $callback_args) use ($widget_id, $requestPath) {
					if (method_exists($this, 'control_callback')) {
						return $this->autoResolveAndCall($widget_id, $this->extraParams['full_path'], $requestPath, $this, 'control_callback', ['post' => $post, 'callback_args' => $callback_args]);
					}
					return null;
				},
				$this->callback_args,
				$this->context,
				$this->priority
			);
		});
	}

}