<?php

namespace WPSPCORE\App\Routes\Actions;

trait ActionsRouteTrait {

	public function register(): void {
		$this->actions();
		$this->wp_actions();
	}

	/*
	 *
	 */

	abstract public function actions();

	abstract public function wp_actions();

}