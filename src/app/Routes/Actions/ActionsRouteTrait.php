<?php

namespace WPSPCORE\App\Routes\Actions;

trait ActionsRouteTrait {

	public function register() {
		$this->actions();
	}

	/*
	 *
	 */

	abstract public function actions();

}