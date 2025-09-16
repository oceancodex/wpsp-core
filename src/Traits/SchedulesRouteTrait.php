<?php

namespace WPSPCORE\Traits;

trait SchedulesRouteTrait {

	use HookRunnerTrait, GroupRoutesTrait;

	public function init(): void {
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

	public function interval(string $name, $interval, string $display): void {
		add_filter('cron_schedules', function($schedules) use ($name, $interval, $display) {
			$schedules[$name] = [
				'interval' => $interval,
				'display'  => $display
			];
			return $schedules;
		});
	}

	public function schedule(string $hook, string $interval, $callback, $useInitClass = false, $customProperties = []): void {
		$callback = $this->prepareCallback($callback, $useInitClass, $customProperties);
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