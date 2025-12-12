<?php

namespace WPSPCORE\App\View;

use WPSPCORE\BaseInstances;

/**
 * @mixin \Illuminate\View\View
 * @mixin \Illuminate\Support\Facades\View
 */
abstract class View extends BaseInstances {

	private \Illuminate\View\View $view;

	/*
	 *
	 */

	public function getView(): \Illuminate\View\View {
		return $this->view;
	}

	public function setView() {
		$this->view = $this->funcs->getApplication('view');
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		return static::__callStatic($method, $arguments);
	}

	public static function __callStatic($method, $arguments) {
		$instance = static::instance();

		$underlineMethod = '_' . $method;
		if (method_exists($instance, $underlineMethod)) {
			return $instance->$underlineMethod(...$arguments);
		}

		return $instance->getView()->$method(...$arguments);
	}

}