<?php

namespace WPSPCORE\Traits;

trait ScheduleRouteTrait {

	public function init(): void {
		$this->intervals();
		$this->schedules();
	}

	/*
	 *
	 */

	public function schedules() {}
	public function intervals() {}

	/*
	 *
	 */

	public function schedule(string $hook, string $interval, $callback, $useInitClass = false, $classArgs = []): void {
		$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
		add_action($hook, $callback);
		if (!wp_next_scheduled($hook)) {
			wp_schedule_event(time(), $interval, $hook);
		}
		register_deactivation_hook(WPSP_PLUGIN_FILE_PATH, function() use ($hook) {
			wp_unschedule_hook($hook);
//			$timestamp = wp_next_scheduled($hook);
//			if ($timestamp) wp_unschedule_event($timestamp, $hook);
		});
	}

	public function interval(string $name, int|string $interval, string $display): void {
		add_filter('cron_schedules', function($schedules) use ($name, $interval, $display) {
			$schedules[$name] = [
				'interval' => $interval,
				'display'  => $display
			];
			return $schedules;
		});
	}

}