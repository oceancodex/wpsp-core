<?php

namespace WPSPCORE\App\WordPress\Schedules;

use WPSPCORE\App\Routes\RouteTrait;
use WPSPCORE\BaseInstances;

abstract class BaseSchedule extends BaseInstances {

	use RouteTrait;

	public $hook              = null;
	public $interval          = null;
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'] ?? null;
		$this->overrideInterval($this->extraParams['interval'] ?? null);
		$this->overrideHook($this->extraParams['full_path'] ?? null);
	}

	/*
	 *
	 */

	public function overrideHook($hook = null) {
		if ($hook && !$this->hook) {
			$this->hook = $hook;
		}
	}

	public function overrideInterval($interval = null) {
		if ($interval && !$this->interval) {
			$this->interval = $interval;
		}
	}

	/*
	 *
	 */

	public function init($hook = null, $interval = null) {
		$hook     = $hook ?? $this->hook;
		$interval = $interval ?? $this->interval;

		// Đăng ký schedule nếu chưa tồn lại.
		if (!wp_next_scheduled($hook)) {
			wp_schedule_event(time(), $interval, $hook);
		}

		// Đăng ký action gắn với schedule.
		add_action($hook, [$this, $this->callback_function . '!']);

		// Xóa schedule khi plugin bị hủy kích hoạt
		register_deactivation_hook($this->funcs->_getMainFilePath(), function() use ($hook) {
			wp_unschedule_hook($hook);
//			$timestamp = wp_next_scheduled($hook);
//			if ($timestamp) wp_unschedule_event($timestamp, $hook);
		});
	}

	/*
	 *
	 */

	public function __call($method, $arguments) {
		/**
		 * Xử lý DI cho callback function tại đây.\
		 * Trong route Schedules khai báo schedule với callback function: "handle"\
		 * Callback function thực tế được add vào WordPress sẽ là: "handle!"\
		 * Sử dụng __call() để bắt call "handle!" và xử lý DI.
		 */
		$method = preg_replace('/!$/', '', $method);
		if (method_exists($this, $method)) {
			$requestPath = trim($this->request->getRequestUri(), '/\\');

			$constructParams = [
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv(),
				[
					'hook'              => $hook ?? $this->hook,
					'interval'          => $interval ?? $this->interval,
					'callback_function' => $this->callback_function,
				],
			];

			$callback   = $this->prepareRouteCallback([$this, $this->callback_function], $constructParams);
			$callParams = $this->getCallParams($this->hook, $this->hook, $requestPath, $this, $this->callback_function);
			$this->resolveAndCall($callback, $callParams);
		}
	}

}