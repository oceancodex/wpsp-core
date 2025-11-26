<?php

namespace WPSPCORE\Routes\Schedules;

use WPSPCORE\Traits\HookRunnerTrait;
use WPSPCORE\Traits\RouteTrait;

trait SchedulesRouteTrait {

	use HookRunnerTrait, RouteTrait;

	public function init() {
		$this->intervals();
		$this->schedules();
		$this->hooks();
	}

	/*
	 *
	 */

	public function intervals() {}

	public function schedules() {}

	/*
	 *
	 */

	public function interval($name, $interval, $display) {
		add_filter('cron_schedules', function($schedules) use ($name, $interval, $display) {
			$schedules[$name] = [
				'interval' => $interval,
				'display'  => $display
			];
			return $schedules;
		});
	}

	public function schedule($hook, $interval, $callback, $useInitClass = false, $customProperties = []) {
		$constructParams = [
			[
				'hook'              => $hook,
				'callback_function' => $callback[1] ?? null,
				'custom_properties' => $customProperties,
			],
		];
		$constructParams = array_merge([
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv()
		], $constructParams);
		$callback = $this->prepareRouteCallback($callback, $useInitClass, $constructParams);
		add_action($hook, $callback);
		if (!wp_next_scheduled($hook)) {
			wp_schedule_event(time(), $interval, $hook);
		}
		register_deactivation_hook($this->funcs->_getMainFilePath(), function() use ($hook) {
			wp_unschedule_hook($hook);
//			$timestamp = wp_next_scheduled($hook);
//			if ($timestamp) wp_unschedule_event($timestamp, $hook);
		});
	}

}